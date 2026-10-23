<?php

declare(strict_types=1);

namespace App\Actions\Photo;

use App\Enums\NotificationType;
use App\Enums\PhotoStatus;
use App\Models\BiodataPhoto;
use App\Models\User;
use App\Services\Notification\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Staff decision on a pending photo. Runs in a transaction on a locked row and
 * refuses a photo that is no longer pending. A rejected photo loses its files
 * at once. The activity log gets ids and statuses only, never the reason.
 */
final class ReviewPhoto
{
    public function __construct(private readonly Notifier $notifier) {}

    public function approve(User $reviewer, int $photoId): BiodataPhoto
    {
        return $this->decide($reviewer, $photoId, PhotoStatus::Approved, null);
    }

    public function reject(User $reviewer, int $photoId, string $reason): BiodataPhoto
    {
        $reason = trim($reason);
        $length = mb_strlen($reason);

        if ($length < 10 || $length > 200) {
            throw ValidationException::withMessages(['rejectReason' => __('photos.admin.errors.reason_invalid')]);
        }

        return $this->decide($reviewer, $photoId, PhotoStatus::Rejected, $reason);
    }

    private function decide(User $reviewer, int $photoId, PhotoStatus $to, ?string $reason): BiodataPhoto
    {
        $photo = DB::transaction(function () use ($reviewer, $photoId, $to, $reason): BiodataPhoto {
            $photo = BiodataPhoto::query()->with('biodata')->lockForUpdate()->findOrFail($photoId);

            Gate::forUser($reviewer)->authorize('review', $photo);

            if ($photo->status !== PhotoStatus::Pending) {
                throw ValidationException::withMessages(['form' => __('photos.admin.errors.not_pending')]);
            }

            $photo->forceFill([
                'status' => $to->value,
                'rejection_reason' => $reason,
                'is_main' => $to === PhotoStatus::Rejected ? false : $photo->is_main,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
            ])->save();

            activity('moderation')
                ->causedBy($reviewer)
                ->performedOn($photo)
                ->event($to->value)
                ->withProperties([
                    'photo_code' => $photo->code,
                    'biodata_code' => $photo->biodata->public_code,
                    'from' => PhotoStatus::Pending->value,
                    'to' => $to->value,
                ])
                ->log('photo.'.$to->value);

            return $photo;
        });

        if ($to === PhotoStatus::Rejected) {
            Storage::disk('photos')->delete($photo->filePaths());
        }

        $this->notifier->photoReviewed(
            $photo,
            $to === PhotoStatus::Approved ? NotificationType::PhotoApproved : NotificationType::PhotoRejected,
        );

        return $photo;
    }
}

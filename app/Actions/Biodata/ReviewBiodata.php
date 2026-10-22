<?php

declare(strict_types=1);

namespace App\Actions\Biodata;

use App\Services\Notification\Notifier;
use App\Enums\BiodataStatus;
use App\Models\Biodata;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Staff decision on a pending biodata: approve or reject.
 *
 * Both decisions run in a transaction on a freshly locked row and refuse to
 * act if the biodata is no longer pending or if the member changed it after
 * the reviewer opened the page (revision mismatch).
 *
 * The activity log gets ids, statuses and the revision only. It never gets
 * profile content, contact details or the free-text rejection reason.
 */
final class ReviewBiodata
{
    public function __construct(private readonly Notifier $notifier) {}

    public function approve(User $reviewer, int $biodataId, int $seenRevision): Biodata
    {
        return $this->decide($reviewer, $biodataId, $seenRevision, BiodataStatus::Approved, null);
    }

    public function reject(User $reviewer, int $biodataId, int $seenRevision, string $reason): Biodata
    {
        $reason = trim($reason);

        if ($reason === '' || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['rejectReason' => __('admin.errors.reason_invalid')]);
        }

        return $this->decide($reviewer, $biodataId, $seenRevision, BiodataStatus::Rejected, $reason);
    }

    private function decide(User $reviewer, int $biodataId, int $seenRevision, BiodataStatus $to, ?string $reason): Biodata
    {
        $decided = DB::transaction(function () use ($reviewer, $biodataId, $seenRevision, $to, $reason): Biodata {
            $biodata = Biodata::query()->lockForUpdate()->findOrFail($biodataId);

            Gate::forUser($reviewer)->authorize('review', $biodata);

            if ($biodata->status !== BiodataStatus::Pending) {
                throw ValidationException::withMessages(['form' => __('admin.errors.not_pending')]);
            }

            if ($biodata->revision !== $seenRevision) {
                throw ValidationException::withMessages(['form' => __('admin.errors.changed')]);
            }

            $from = $biodata->status;

            $biodata->forceFill([
                'status' => $to->value,
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
            ])->save();

            activity('moderation')
                ->causedBy($reviewer)
                ->performedOn($biodata)
                ->event($to->value)
                ->withProperties([
                    'public_code' => $biodata->public_code,
                    'from' => $from->value,
                    'to' => $to->value,
                    'revision' => $biodata->revision,
                ])
                ->log('biodata.'.$to->value);

            return $biodata;
        });

        $this->notifier->biodataReviewed($decided);

        return $decided;
    }
}

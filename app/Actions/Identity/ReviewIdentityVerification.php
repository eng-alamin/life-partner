<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Enums\IdentityRejectionReason;
use App\Enums\IdentityStatus;
use App\Enums\NotificationType;
use App\Models\Biodata;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\Identity\IdentityFileStore;
use App\Services\Notification\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Staff decide an identity request.
 *
 * - The request is found by its code, and the policy is checked before and again under a row lock.
 * - Only a pending request can be decided, once. Nobody decides their own request.
 * - Approve sets the "identity verified" badge on the member's biodata.
 * - Reject needs one reason from a fixed list (no free text).
 * - After the decision the pictures are deleted and the request stops being "open", so the
 *   member may send a new request after a rejection.
 * - The activity log holds only the request code (and the fixed reason).
 */
final class ReviewIdentityVerification
{
    public function __construct(
        private readonly IdentityFileStore $files,
        private readonly Notifier $notifier,
    ) {}

    public function approve(User $staff, string $code): IdentityVerification
    {
        return $this->decide($staff, $code, IdentityStatus::Approved, null);
    }

    public function reject(User $staff, string $code, string $reason): IdentityVerification
    {
        $reasonCase = IdentityRejectionReason::tryFrom($reason);

        if ($reasonCase === null) {
            throw ValidationException::withMessages(['reason' => __('admin_identity.errors.reason_invalid')]);
        }

        return $this->decide($staff, $code, IdentityStatus::Rejected, $reasonCase);
    }

    private function decide(User $staff, string $code, IdentityStatus $outcome, ?IdentityRejectionReason $reason): IdentityVerification
    {
        $early = IdentityVerification::query()->where('code', $code)->firstOrFail();
        Gate::forUser($staff)->authorize('review', $early);

        $paths = [];

        $verification = DB::transaction(function () use ($staff, $code, $outcome, $reason, &$paths): IdentityVerification {
            $verification = IdentityVerification::query()->where('code', $code)->lockForUpdate()->firstOrFail();

            Gate::forUser($staff)->authorize('review', $verification);

            if (! $verification->isPending()) {
                throw ValidationException::withMessages(['form' => __('admin_identity.errors.not_reviewable')]);
            }

            if ($outcome === IdentityStatus::Approved) {
                $biodata = Biodata::query()->where('user_id', $verification->user_id)->lockForUpdate()->first();

                if ($biodata === null) {
                    throw ValidationException::withMessages(['form' => __('admin_identity.errors.no_biodata')]);
                }

                $biodata->forceFill(['identity_verified_at' => now()])->save();
            }

            $paths = [$verification->front_path, $verification->back_path];

            $verification->forceFill([
                'status' => $outcome->value,
                'open_key' => null,
                'front_path' => null,
                'back_path' => null,
                'files_deleted_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => $staff->getKey(),
                'rejection_reason' => $reason?->value,
            ])->save();

            $properties = ['identity_code' => $verification->code];

            if ($reason !== null) {
                $properties['reason'] = $reason->value;
            }

            activity('identity')
                ->causedBy($staff)
                ->withProperties($properties)
                ->log('identity.'.$outcome->value);

            return $verification;
        });

        // The decision is saved. Now the pictures go.
        $this->files->delete(...$paths);

        $this->notifier->toUser((int) $verification->user_id, $outcome === IdentityStatus::Approved
            ? NotificationType::IdentityApproved
            : NotificationType::IdentityRejected);

        return $verification;
    }
}

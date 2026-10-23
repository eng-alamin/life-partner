<?php

declare(strict_types=1);

namespace App\Actions\Interest;

use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use App\Models\Interest;
use App\Services\Interest\GuardianLinks;
use App\Services\Interest\InterestTransitions;
use App\Services\Notification\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The guardian approves or declines through the one-time link, without logging
 * in. The link works once: after a decision the hash is removed.
 *
 * Approve: guardian_status becomes "approved".
 * Decline: guardian_status becomes "declined" and the interest is closed as
 * declined (actor: guardian), so the pair is free again and the sender's normal
 * decline cooldown applies. The reason is never recorded.
 *
 * A wrong, used, expired or unrelated link all give the same message.
 */
final class DecideGuardianApproval
{
    public function __construct(
        private readonly GuardianLinks $links,
        private readonly InterestTransitions $transitions,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(string $token, bool $approve): Interest
    {
        $interest = DB::transaction(function () use ($token, $approve): Interest {
            $interest = $this->links->findOpen($token, lock: true);

            if ($interest === null) {
                throw ValidationException::withMessages(['form' => __('guardian.errors.invalid_link')]);
            }

            $interest->forceFill([
                'guardian_status' => ($approve ? GuardianApprovalStatus::Approved : GuardianApprovalStatus::Declined)->value,
                'guardian_decided_at' => now(),
                'guardian_token_hash' => null,
                'guardian_token_expires_at' => null,
            ]);

            if ($approve) {
                $interest->save();
            } else {
                $this->transitions->move($interest, InterestStatus::Declined, InterestActor::Guardian);
            }

            activity('interest')
                ->performedOn($interest)
                ->withProperties(['interest_code' => $interest->code])
                ->log($approve ? 'interest.guardian_approved' : 'interest.guardian_declined');

            return $interest;
        });

        $this->notifier->guardianDecided($interest);

        return $interest;
    }
}

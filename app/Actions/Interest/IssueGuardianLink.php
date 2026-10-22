<?php

declare(strict_types=1);

namespace App\Actions\Interest;

use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;
use App\Services\Interest\GuardianLinks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The receiver creates a one-time link for their guardian after accepting.
 * A new link replaces the old one. Only the hash is stored; the plain token is
 * returned once to the caller and never saved.
 */
final class IssueGuardianLink
{
    public function __construct(private readonly GuardianLinks $links) {}

    /** @return string the plain token (to build the link) */
    public function __invoke(User $user, string $code): string
    {
        return DB::transaction(function () use ($user, $code): string {
            $receiver = Biodata::query()->where('user_id', $user->getKey())->firstOrFail();

            $interest = Interest::query()
                ->where('code', $code)
                ->where('receiver_biodata_id', $receiver->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $interest->setRelation('receiver', $receiver);

            Gate::forUser($user)->authorize('issueGuardianLink', $interest);

            if ($interest->status !== InterestStatus::Accepted
                || $interest->guardian_status !== GuardianApprovalStatus::Pending) {
                throw ValidationException::withMessages(['form' => __('guardian.errors.not_waiting')]);
            }

            $token = Str::random(GuardianLinks::TOKEN_LENGTH);

            $interest->forceFill([
                'guardian_token_hash' => $this->links->hash($token),
                'guardian_token_expires_at' => now()->addDays((int) config('lifepartner.interests.guardian_link_days')),
            ])->save();

            activity('interest')
                ->causedBy($user)
                ->performedOn($interest)
                ->withProperties(['interest_code' => $interest->code])
                ->log('interest.guardian_link_issued');

            return $token;
        });
    }
}

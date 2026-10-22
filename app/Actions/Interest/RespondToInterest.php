<?php

declare(strict_types=1);

namespace App\Actions\Interest;

use App\Services\Notification\Notifier;
use App\Enums\BiodataStatus;
use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;
use App\Services\Block\BlockGuard;
use App\Services\Interest\InterestTransitions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The receiver accepts or declines a pending interest. The request is looked up
 * inside the member's own received interests, so a code that belongs to someone
 * else simply finds nothing (404).
 *
 * Accepting does not by itself reveal contact details: if the receiver's
 * guardian still has to approve, guardian_status stays "pending" and a later
 * step handles it.
 */
final class RespondToInterest
{
    public function __construct(
        private readonly InterestTransitions $transitions,
        private readonly BlockGuard $blocks,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $user, string $code, InterestStatus $decision): Interest
    {
        if (! in_array($decision, [InterestStatus::Accepted, InterestStatus::Declined], true)) {
            throw new InvalidArgumentException('A response must be accepted or declined.');
        }

        $expired = false;

        $interest = DB::transaction(function () use ($user, $code, $decision, &$expired): Interest {
            $receiver = Biodata::query()->where('user_id', $user->getKey())->firstOrFail();

            $interest = Interest::query()
                ->with('sender')
                ->where('code', $code)
                ->where('receiver_biodata_id', $receiver->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $interest->setRelation('receiver', $receiver);

            Gate::forUser($user)->authorize('respond', $interest);

            if ($interest->status !== InterestStatus::Pending) {
                throw $this->error('not_open');
            }

            // The scheduled job may not have run yet, so check the date here too.
            if ($interest->expires_at !== null && $interest->expires_at->isPast()) {
                $this->transitions->move($interest, InterestStatus::Expired, InterestActor::System);
                $expired = true;

                return $interest;
            }

            if ($decision === InterestStatus::Accepted && $interest->sender?->status !== BiodataStatus::Approved) {
                throw $this->error('sender_unavailable');
            }

            // Pending requests are cancelled when a block is made; this guards a race.
            if (
                $decision === InterestStatus::Accepted
                && $interest->sender !== null
                && $this->blocks->between((int) $user->getKey(), (int) $interest->sender->user_id)
            ) {
                throw $this->error('sender_unavailable');
            }

            $this->transitions->move($interest, $decision, InterestActor::Receiver, $user);

            return $interest;
        });

        if ($expired) {
            throw $this->error('expired');
        }

        $this->notifier->interestAnswered($interest);

        return $interest;
    }

    private function error(string $key): ValidationException
    {
        return ValidationException::withMessages(['form' => __('interests.errors.'.$key)]);
    }
}

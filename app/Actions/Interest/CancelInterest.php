<?php

declare(strict_types=1);

namespace App\Actions\Interest;

use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;
use App\Services\Interest\InterestTransitions;
use App\Services\Notification\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * The sender withdraws a pending interest. The request is looked up inside the
 * member's own sent interests, so someone else's code finds nothing (404).
 */
final class CancelInterest
{
    public function __construct(
        private readonly InterestTransitions $transitions,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $user, string $code): Interest
    {
        $interest = DB::transaction(function () use ($user, $code): Interest {
            $sender = Biodata::query()->where('user_id', $user->getKey())->firstOrFail();

            $interest = Interest::query()
                ->where('code', $code)
                ->where('sender_biodata_id', $sender->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $interest->setRelation('sender', $sender);

            Gate::forUser($user)->authorize('cancel', $interest);

            if ($interest->status !== InterestStatus::Pending) {
                throw ValidationException::withMessages(['form' => __('interests.errors.not_open')]);
            }

            $this->transitions->move($interest, InterestStatus::Cancelled, InterestActor::Sender, $user);

            return $interest;
        });

        $this->notifier->interestCancelled($interest);

        return $interest;
    }
}

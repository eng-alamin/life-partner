<?php

declare(strict_types=1);

namespace App\Services\Interest;

use App\Enums\InterestActor;
use App\Enums\InterestStatus;
use App\Models\Interest;
use App\Models\InterestStatusHistory;
use App\Models\User;

/**
 * The only place that changes the status of an interest and writes the
 * matching history row. Callers must already be inside a database transaction
 * and must hold a row lock on the interest.
 */
final class InterestTransitions
{
    /** Writes the very first history row of a new request. */
    public function recordCreated(Interest $interest, User $sender): void
    {
        $this->log($interest, null, InterestStatus::Pending, InterestActor::Sender, $sender);
    }

    /** Moves a pending request to its final status and records who did it. */
    public function move(Interest $interest, InterestStatus $to, InterestActor $actor, ?User $by = null): void
    {
        $from = $interest->status;

        $interest->forceFill([
            'status' => $to->value,
            'responded_at' => now(),
        ])->save();

        $this->log($interest, $from, $to, $actor, $by);
    }

    private function log(Interest $interest, ?InterestStatus $from, InterestStatus $to, InterestActor $actor, ?User $by): void
    {
        $history = new InterestStatusHistory;
        $history->forceFill([
            'interest_id' => $interest->getKey(),
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'actor_type' => $actor->value,
            'actor_user_id' => $by?->getKey(),
        ])->save();
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

/**
 * Answers "does this member have a membership right now, and what does it give?".
 * A period counts only between its start and its end. A period bought while another
 * one is running starts when that one ends, so it is not "current" yet.
 */
final class ActiveMembership
{
    public function current(int $userId): ?Subscription
    {
        return Subscription::query()
            ->where('user_id', $userId)
            ->where('status', SubscriptionStatus::Active->value)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->orderByDesc('daily_interest_limit')
            ->orderByDesc('ends_at')
            ->first();
    }

    /** New interests one member may send per rolling 24 hours: the free limit, or the plan's if it is higher. */
    public function dailyInterestLimit(int $userId): int
    {
        $free = (int) config('lifepartner.interests.daily_limit');
        $plan = $this->current($userId)?->daily_interest_limit;

        return $plan === null ? $free : max($free, (int) $plan);
    }
}

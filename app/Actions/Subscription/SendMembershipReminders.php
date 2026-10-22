<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Notification\Notifier;

/**
 * Tells a member that a paid period is about to end. Run once a day.
 *
 * Rules:
 * - Only running periods that end within "reminder_days" are looked at.
 * - Each period is claimed with one atomic update on `reminded_at`, so a second
 *   run (or two runs at once) can never send the same reminder twice.
 * - No reminder when another active period already continues after this one
 *   (the member renewed), or when the whole period is no longer than the reminder window
 *   (it would be "ending soon" from the first minute).
 *
 * Returns how many reminders were sent.
 */
final class SendMembershipReminders
{
    public function __construct(private readonly Notifier $notifier) {}

    public function __invoke(): int
    {
        $window = max(0, (int) config('lifepartner.billing.reminder_days'));

        if ($window === 0) {
            return 0;
        }

        $sent = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->whereNull('reminded_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->where('ends_at', '<=', now()->addDays($window))
            ->orderBy('id')
            ->chunkById(200, function ($subscriptions) use ($window, &$sent): void {
                foreach ($subscriptions as $subscription) {
                    $claimed = Subscription::query()
                        ->whereKey($subscription->getKey())
                        ->whereNull('reminded_at')
                        ->update(['reminded_at' => now()]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    if ($this->isTooShort($subscription, $window) || $this->isContinued($subscription)) {
                        continue;
                    }

                    $this->notifier->membershipEnding($subscription, $this->daysLeft($subscription));
                    $sent++;
                }
            });

        return $sent;
    }

    private function isTooShort(Subscription $subscription, int $window): bool
    {
        if ($subscription->starts_at === null || $subscription->ends_at === null) {
            return true;
        }

        return $subscription->starts_at->diffInHours($subscription->ends_at) <= $window * 24;
    }

    /** Another active period (running or already bought for later) ends after this one. */
    private function isContinued(Subscription $subscription): bool
    {
        return Subscription::query()
            ->where('user_id', $subscription->user_id)
            ->where('status', SubscriptionStatus::Active->value)
            ->where('ends_at', '>', $subscription->ends_at)
            ->whereKeyNot($subscription->getKey())
            ->exists();
    }

    private function daysLeft(Subscription $subscription): int
    {
        return max(1, (int) ceil(now()->diffInHours($subscription->ends_at, false) / 24));
    }
}

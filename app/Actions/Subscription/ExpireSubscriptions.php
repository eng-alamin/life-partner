<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Notification\Notifier;

/**
 * Marks membership periods whose end has passed as expired. Run every hour.
 * A period that has ended never gives its benefits anyway (ActiveMembership checks
 * the dates), so this keeps the status honest and tells the member once.
 *
 * Each period is changed with one conditional update (still "active"), so a second
 * run cannot count or announce it again. The member is told only when no other active
 * period continues, and only once (`expired_notice_at`). Returns how many were changed.
 */
final class ExpireSubscriptions
{
    public function __construct(private readonly Notifier $notifier) {}

    public function __invoke(): int
    {
        $count = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->where('ends_at', '<=', now())
            ->orderBy('id')
            ->chunkById(200, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    $changed = Subscription::query()
                        ->whereKey($subscription->getKey())
                        ->where('status', SubscriptionStatus::Active->value)
                        ->update(['status' => SubscriptionStatus::Expired->value, 'updated_at' => now()]);

                    if ($changed !== 1) {
                        continue;
                    }

                    $count++;
                    $this->tellMember($subscription);
                }
            });

        return $count;
    }

    private function tellMember(Subscription $subscription): void
    {
        $continues = Subscription::query()
            ->where('user_id', $subscription->user_id)
            ->where('status', SubscriptionStatus::Active->value)
            ->where('ends_at', '>', now())
            ->exists();

        if ($continues) {
            return;
        }

        $claimed = Subscription::query()
            ->whereKey($subscription->getKey())
            ->whereNull('expired_notice_at')
            ->update(['expired_notice_at' => now()]);

        if ($claimed === 1) {
            $this->notifier->membershipEnded($subscription);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates the membership period that a paid membership payment buys.
 *
 * - Once per payment: calling it again returns the first period (the unique
 *   payment_id key in the database is the final guard).
 * - A new period starts when the member's current one ends, so no paid day is lost
 *   and periods never overlap. It is stored as "active" with a future start.
 * - The plan's name and limit come from the payment snapshot, never from the plan row.
 */
final class StartSubscription
{
    public function __invoke(User $user, Payment $payment): Subscription
    {
        if ($payment->duration_days === null || $payment->duration_days < 1) {
            throw new InvalidArgumentException('A membership payment needs a length in days.');
        }

        if ((int) $payment->user_id !== (int) $user->getKey()) {
            throw new InvalidArgumentException('The payment belongs to another member.');
        }

        try {
            return DB::transaction(fn (): Subscription => $this->start($user, $payment));
        } catch (UniqueConstraintViolationException $e) {
            $existing = Subscription::query()->where('payment_id', $payment->getKey())->first();

            if ($existing === null) {
                throw $e;
            }

            return $existing;
        }
    }

    private function start(User $user, Payment $payment): Subscription
    {
        User::query()->whereKey($user->getKey())->lockForUpdate()->first();

        $existing = Subscription::query()->where('payment_id', $payment->getKey())->first();

        if ($existing !== null) {
            return $existing;
        }

        $latestEnd = Subscription::query()
            ->where('user_id', $user->getKey())
            ->where('status', SubscriptionStatus::Active->value)
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->value('ends_at');

        $starts = $latestEnd !== null ? Carbon::parse($latestEnd) : now();
        $ends = $starts->copy()->addDays((int) $payment->duration_days);

        $subscription = new Subscription;
        $subscription->forceFill([
            'user_id' => $user->getKey(),
            'payment_id' => $payment->getKey(),
            'plan_code' => $payment->plan_code,
            'plan_name' => $payment->plan_name,
            'daily_interest_limit' => $payment->daily_interest_limit,
            'status' => SubscriptionStatus::Active->value,
            'starts_at' => $starts,
            'ends_at' => $ends,
        ])->save();

        activity('billing')
            ->causedBy($user)
            ->withProperties(['payment_code' => $payment->code, 'plan_code' => $payment->plan_code, 'days' => (int) $payment->duration_days])
            ->log('subscription.started');

        return $subscription;
    }
}

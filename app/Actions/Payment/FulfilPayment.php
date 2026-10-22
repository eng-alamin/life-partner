<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Actions\Credit\GrantCredits;
use App\Actions\Subscription\StartSubscription;
use App\Enums\CreditReason;
use App\Enums\PlanKind;
use App\Models\Payment;
use App\Models\User;
use RuntimeException;

/**
 * Gives the member what a paid payment bought: credits, or a membership period
 * (with its credits, which expire when the period ends).
 *
 * It must be called inside the transaction that marked the payment paid, with the
 * payment row locked. It is safe to call twice: fulfilled_at stops a second run, and
 * the unique keys under GrantCredits and StartSubscription stop a second grant even
 * if two runs ever met. What is granted comes from the payment snapshot, never
 * from the plan row, so a later price or plan change cannot change it.
 */
final class FulfilPayment
{
    public function __construct(
        private readonly GrantCredits $grant,
        private readonly StartSubscription $subscriptions,
    ) {}

    public function __invoke(Payment $payment): void
    {
        if ($payment->fulfilled_at !== null) {
            return;
        }

        $user = $payment->user_id === null ? null : User::query()->find($payment->user_id);

        if ($user === null) {
            throw new RuntimeException('The member of this payment no longer exists.');
        }

        if ($payment->plan_kind === PlanKind::Membership) {
            $subscription = ($this->subscriptions)($user, $payment);

            if ($payment->credits > 0) {
                ($this->grant)($user, (int) $payment->credits, CreditReason::MembershipGrant, $payment, $subscription->ends_at);
            }
        } elseif ($payment->credits > 0) {
            ($this->grant)($user, (int) $payment->credits, CreditReason::Purchase, $payment);
        }

        $payment->forceFill(['fulfilled_at' => now()])->save();
    }
}

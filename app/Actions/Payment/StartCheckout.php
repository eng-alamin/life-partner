<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\BiodataStatus;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Biodata;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Starts a manual bKash payment for a plan, found by its code.
 *
 * - The amount, name, credits and days are copied from OUR plan row. Nothing about
 *   the price comes from the browser.
 * - The member needs a verified email and an approved biodata, so they pay for
 *   something they can use.
 * - A member has one unfinished payment at a time: starting the same plan again
 *   returns it, starting another plan cancels the old one.
 * - Limits: new payments per hour, and payments waiting for staff.
 * - The member's user row is locked while the rules run, so two quick taps cannot
 *   slip past a limit.
 */
final class StartCheckout
{
    public function __invoke(User $user, string $planCode): Payment
    {
        Gate::forUser($user)->authorize('create', Payment::class);

        if (trim((string) config('lifepartner.billing.bkash_number')) === '') {
            throw $this->error('not_configured');
        }

        return DB::transaction(fn (): Payment => $this->start($user, $planCode));
    }

    private function start(User $user, string $planCode): Payment
    {
        User::query()->whereKey($user->getKey())->lockForUpdate()->first();

        $hasApproved = Biodata::query()
            ->where('user_id', $user->getKey())
            ->where('status', BiodataStatus::Approved->value)
            ->exists();

        if (! $hasApproved) {
            throw $this->error('biodata_required');
        }

        $plan = Plan::query()->active()->where('code', $planCode)->first();

        if ($plan === null || $plan->price_minor < 1) {
            throw $this->error('plan_unavailable');
        }

        $open = Payment::query()
            ->where('user_id', $user->getKey())
            ->where('status', PaymentStatus::Pending->value)
            ->get();

        foreach ($open as $payment) {
            if ($payment->plan_code === $plan->code && $payment->amount_minor === $plan->price_minor) {
                return $payment;
            }
        }

        $waiting = Payment::query()
            ->where('user_id', $user->getKey())
            ->where('status', PaymentStatus::UnderReview->value)
            ->count();

        if ($waiting >= (int) config('lifepartner.billing.max_under_review')) {
            throw $this->error('too_many_waiting');
        }

        $recent = Payment::query()
            ->where('user_id', $user->getKey())
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= (int) config('lifepartner.billing.max_pending_checkouts_per_hour')) {
            throw $this->error('too_many_checkouts');
        }

        foreach ($open as $payment) {
            $payment->forceFill([
                'status' => PaymentStatus::Cancelled->value,
                'failure_reason' => 'replaced',
            ])->save();
        }

        $payment = new Payment;
        $payment->forceFill([
            'user_id' => $user->getKey(),
            'plan_id' => $plan->getKey(),
            'plan_code' => $plan->code,
            'plan_name' => $plan->name_en,
            'plan_kind' => $plan->kind->value,
            'credits' => $plan->credits,
            'duration_days' => $plan->duration_days,
            'daily_interest_limit' => $plan->daily_interest_limit,
            'amount_minor' => $plan->price_minor,
            'currency' => $plan->currency,
            'gateway' => PaymentGateway::ManualBkash->value,
            'status' => PaymentStatus::Pending->value,
        ])->save();

        $event = new PaymentEvent;
        $event->forceFill([
            'payment_id' => $payment->getKey(),
            'gateway' => PaymentGateway::ManualBkash->value,
            'event_type' => 'init',
            'payload' => ['plan_code' => $plan->code, 'amount_minor' => $plan->price_minor],
            'processed_at' => now(),
        ])->save();

        activity('payment')
            ->causedBy($user)
            ->withProperties(['payment_code' => $payment->code, 'plan_code' => $plan->code, 'amount_minor' => $plan->price_minor])
            ->log('payment.started');

        return $payment;
    }

    private function error(string $key): ValidationException
    {
        return ValidationException::withMessages(['form' => __('billing.errors.'.$key)]);
    }
}

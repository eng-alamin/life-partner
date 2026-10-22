<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * A member cancels their own payment while it is still pending (nothing submitted).
 * Once the member has submitted the TrxID or screenshot, only staff decide.
 */
final class CancelPayment
{
    public function __invoke(User $user, string $paymentCode): Payment
    {
        return DB::transaction(function () use ($user, $paymentCode): Payment {
            $payment = Payment::query()
                ->where('code', $paymentCode)
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($user)->authorize('manage', $payment);

            if ($payment->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages(['form' => __('billing.errors.not_cancellable')]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Cancelled->value,
                'failure_reason' => 'cancelled_by_member',
            ])->save();

            $event = new PaymentEvent;
            $event->forceFill([
                'payment_id' => $payment->getKey(),
                'gateway' => $payment->gateway->value,
                'event_type' => 'cancelled',
                'payload' => ['by' => 'member'],
                'processed_at' => now(),
            ])->save();

            activity('payment')
                ->causedBy($user)
                ->withProperties(['payment_code' => $payment->code])
                ->log('payment.cancelled');

            return $payment;
        });
    }
}

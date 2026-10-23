<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentEventRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Staff say they dealt with a bKash payment that was flagged "needs attention" (they checked the bKash
 * merchant panel and refunded or sorted it out by hand). This only removes the flag from the list; it never
 * grants anything and never changes the payment's status.
 *
 * - Only staff who may review payments, never for a payment of their own account.
 * - Only a bKash API payment that was flagged, not handled yet, and not paid or refunded.
 * - One transaction with the payment row locked, so two staff members cannot both resolve it.
 * - The event and the activity log hold the payment code only.
 */
final class ResolveBkashAttention
{
    public function __construct(private readonly PaymentEventRecorder $events) {}

    public function __invoke(User $staff, string $paymentCode): Payment
    {
        $early = Payment::query()->where('code', $paymentCode)->firstOrFail();
        Gate::forUser($staff)->authorize('review', $early);

        return DB::transaction(function () use ($staff, $paymentCode): Payment {
            $payment = Payment::query()->where('code', $paymentCode)->lockForUpdate()->firstOrFail();

            Gate::forUser($staff)->authorize('review', $payment);

            $flagged = $payment->events()->where('event_type', 'needs_attention')->exists();
            $handled = $payment->events()->where('event_type', 'attention_resolved')->exists();

            if ($payment->gateway !== PaymentGateway::Bkash
                || ! $flagged
                || $handled
                || in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true)) {
                throw ValidationException::withMessages(['form' => __('admin_payments.errors.not_flagged')]);
            }

            $this->events->record($payment, 'attention_resolved', ['by' => 'staff']);

            activity('payment')
                ->causedBy($staff)
                ->withProperties(['payment_code' => $payment->code])
                ->log('payment.attention_resolved');

            return $payment;
        });
    }
}

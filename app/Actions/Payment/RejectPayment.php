<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\NotificationType;
use App\Services\Notification\Notifier;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentEventRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Staff reject a payment that is under_review (for example: no such transaction on
 * the statement, wrong amount). A reason is required. Nothing is granted.
 *
 * The TrxID the member typed is released, so a rejected claim does not keep a real
 * transaction reserved. The payer number and the screenshot stay for the audit trail.
 * The member must start a new checkout; the old payment stays as history.
 */
final class RejectPayment
{
    private const REASON_MIN = 3;

    private const REASON_MAX = 255;

    public function __construct(
        private readonly PaymentEventRecorder $events,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $staff, string $paymentCode, string $reason): Payment
    {
        $reason = trim(preg_replace('/\s+/u', ' ', $reason) ?? '');
        $length = mb_strlen($reason);

        if ($length < self::REASON_MIN || $length > self::REASON_MAX) {
            throw ValidationException::withMessages(['reason' => __('admin_payments.errors.reason_invalid', ['min' => self::REASON_MIN, 'max' => self::REASON_MAX])]);
        }

        $early = Payment::query()->where('code', $paymentCode)->firstOrFail();
        Gate::forUser($staff)->authorize('review', $early);

        $payment = DB::transaction(function () use ($staff, $paymentCode, $reason): Payment {
            $payment = Payment::query()->where('code', $paymentCode)->lockForUpdate()->firstOrFail();

            Gate::forUser($staff)->authorize('review', $payment);

            if ($payment->status !== PaymentStatus::UnderReview) {
                throw ValidationException::withMessages(['form' => __('admin_payments.errors.not_reviewable')]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Failed->value,
                'failure_reason' => 'rejected',
                'review_note' => $reason,
                'gateway_ref' => null,
                'reviewed_by' => $staff->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $this->events->record($payment, 'rejected', ['by' => 'staff']);

            activity('payment')
                ->causedBy($staff)
                ->withProperties([
                    'payment_code' => $payment->code,
                    'plan_code' => $payment->plan_code,
                    'amount_minor' => $payment->amount_minor,
                ])
                ->log('payment.rejected');

            return $payment;
        });

        $this->notifier->paymentDecided($payment, NotificationType::PaymentRejected);

        return $payment;
    }
}

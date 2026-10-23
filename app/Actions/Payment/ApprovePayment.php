<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Notification\Notifier;
use App\Services\Payment\PaymentEventRecorder;
use App\Support\TrxId;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Staff confirm that the money arrived (they compared amount, time and sender with the
 * bKash statement). One transaction, with the payment row locked:
 * under_review -> paid, then the member receives what the plan gives.
 *
 * - Only an admin with payments.manage, and never for a payment of their own account.
 * - Only a manual payment that is under_review can be approved. A second click, or a
 *   second admin at the same moment, finds it already paid and changes nothing.
 * - If the member gave only a screenshot, staff MUST enter the TrxID. So every approved
 *   payment has a TrxID, and the database unique key always protects against paying
 *   twice with the same transaction. A TrxID the member already gave is not changed here.
 * - The activity log and the event keep the payment code, plan code and amount only:
 *   never the TrxID, the payer number or the file.
 */
final class ApprovePayment
{
    public function __construct(
        private readonly FulfilPayment $fulfil,
        private readonly PaymentEventRecorder $events,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $staff, string $paymentCode, ?string $trxId = null): Payment
    {
        $entered = TrxId::clean($trxId);

        $early = Payment::query()->where('code', $paymentCode)->firstOrFail();
        Gate::forUser($staff)->authorize('review', $early);

        try {
            $payment = DB::transaction(fn (): Payment => $this->approve($staff, $paymentCode, $entered));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['trx_id' => __('billing.errors.trx_used')]);
        }

        $this->notifier->paymentDecided($payment, NotificationType::PaymentApproved);

        return $payment;
    }

    private function approve(User $staff, string $paymentCode, ?string $entered): Payment
    {
        $payment = Payment::query()->where('code', $paymentCode)->lockForUpdate()->firstOrFail();

        Gate::forUser($staff)->authorize('review', $payment);

        if ($payment->status !== PaymentStatus::UnderReview || ! $payment->gateway->isManual()) {
            throw ValidationException::withMessages(['form' => __('admin_payments.errors.not_reviewable')]);
        }

        if ($payment->user_id === null) {
            throw ValidationException::withMessages(['form' => __('admin_payments.errors.no_member')]);
        }

        $reference = $payment->gateway_ref ?? $entered;

        if ($reference === null) {
            throw ValidationException::withMessages(['trx_id' => __('admin_payments.errors.trx_required')]);
        }

        if ($payment->gateway_ref === null) {
            $taken = Payment::query()
                ->where('ref_scope', $payment->gateway->refScope())
                ->where('gateway_ref', $reference)
                ->whereKeyNot($payment->getKey())
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages(['trx_id' => __('billing.errors.trx_used')]);
            }
        }

        $payment->forceFill([
            'gateway_ref' => $reference,
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
            'failure_reason' => null,
            'review_note' => null,
            'reviewed_by' => $staff->getKey(),
            'reviewed_at' => now(),
        ])->save();

        ($this->fulfil)($payment);

        $this->events->record($payment, 'approved', ['by' => 'staff']);

        activity('payment')
            ->causedBy($staff)
            ->withProperties([
                'payment_code' => $payment->code,
                'plan_code' => $payment->plan_code,
                'amount_minor' => $payment->amount_minor,
            ])
            ->log('payment.approved');

        return $payment;
    }
}

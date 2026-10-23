<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\CreditReason;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CreditLedgerEntry;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Notification\Notifier;
use App\Services\Payment\PaymentEventRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Staff record a refund that they already paid back by hand in bKash. This changes
 * our records only; it never moves money.
 *
 * The payment becomes "refunded". What happens to what the member received is two
 * separate, explicit choices:
 * - remove the credits this payment gave (a reversal row in the ledger; if some were
 *   already spent the balance may go below zero and is shown as it is);
 * - cancel the membership period this payment bought.
 *
 * Only a paid payment can be refunded, once. The member's row is locked while the
 * ledger changes.
 */
final class RefundPayment
{
    public function __construct(
        private readonly PaymentEventRecorder $events,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $staff, string $paymentCode, bool $removeCredits, bool $cancelSubscription): Payment
    {
        $early = Payment::query()->where('code', $paymentCode)->firstOrFail();
        Gate::forUser($staff)->authorize('review', $early);

        $refunded = DB::transaction(function () use ($staff, $paymentCode, $removeCredits, $cancelSubscription): Payment {
            $payment = Payment::query()->where('code', $paymentCode)->lockForUpdate()->firstOrFail();

            Gate::forUser($staff)->authorize('review', $payment);

            if ($payment->status !== PaymentStatus::Paid) {
                throw ValidationException::withMessages(['form' => __('admin_payments.errors.not_refundable')]);
            }

            if ($payment->user_id !== null) {
                User::query()->whereKey($payment->user_id)->lockForUpdate()->first();
            }

            $removed = $removeCredits ? $this->removeCredits($payment) : 0;
            $cancelled = $cancelSubscription && $this->cancelSubscription($payment);

            $payment->forceFill(['status' => PaymentStatus::Refunded->value])->save();

            $this->events->record($payment, 'refunded', [
                'by' => 'staff',
                'credits_removed' => $removed,
                'subscription_cancelled' => $cancelled,
            ]);

            activity('payment')
                ->causedBy($staff)
                ->withProperties([
                    'payment_code' => $payment->code,
                    'plan_code' => $payment->plan_code,
                    'amount_minor' => $payment->amount_minor,
                    'credits_removed' => $removed,
                    'subscription_cancelled' => $cancelled,
                ])
                ->log('payment.refunded');

            return $payment;
        });

        $this->notifier->paymentDecided($refunded, NotificationType::PaymentRefunded);

        return $refunded;
    }

    /** Writes the reversal and returns how many credits were taken back. */
    private function removeCredits(Payment $payment): int
    {
        if ($payment->user_id === null) {
            return 0;
        }

        $granted = (int) CreditLedgerEntry::query()
            ->where('payment_id', $payment->getKey())
            ->where('delta', '>', 0)
            ->sum('delta');

        if ($granted < 1) {
            return 0;
        }

        $entry = new CreditLedgerEntry;
        $entry->forceFill([
            'user_id' => $payment->user_id,
            'delta' => -$granted,
            'reason' => CreditReason::RefundReversal->value,
            'payment_id' => $payment->getKey(),
            'grant_key' => 'payment-'.$payment->getKey().'-'.CreditReason::RefundReversal->value,
        ])->save();

        return $granted;
    }

    private function cancelSubscription(Payment $payment): bool
    {
        $subscription = Subscription::query()
            ->where('payment_id', $payment->getKey())
            ->where('status', SubscriptionStatus::Active->value)
            ->first();

        if ($subscription === null) {
            return false;
        }

        $subscription->forceFill([
            'status' => SubscriptionStatus::Cancelled->value,
            'cancelled_at' => now(),
        ])->save();

        return true;
    }
}

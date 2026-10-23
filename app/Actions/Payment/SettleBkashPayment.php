<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\NotificationType;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\Notification\Notifier;
use App\Services\Payment\BkashClient;
use App\Services\Payment\BkashException;
use App\Services\Payment\BkashOutcomeUnknownException;
use App\Services\Payment\PaymentEventRecorder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finishes a bKash API payment after the member came back from the bKash page.
 *
 * The browser only says "success", "failure" or "cancel" in the address. That is NEVER proof of payment:
 * money counts only when bKash's own Execute answer says "Completed" AND the payment id, invoice number,
 * currency and amount in that answer match OUR payment row (the amount is compared in integer poisha).
 *
 * - Only the payment's owner can finish it (the payment is found by bKash's session id AND the member).
 * - Execute is called OUTSIDE a database transaction, and only for a payment that is still pending. A payment
 *   that was cancelled (replaced by another plan, or stale) is never executed, so no money can move for it.
 * - An unclear Execute answer (timeout, server error) leaves the payment pending. The next step (Query Payment)
 *   settles it later. A "not completed" answer never fails the payment: it might still be paid.
 * - Paying is one transaction with the payment row locked: paid, the TrxID, then credits or membership. A
 *   second call (back button, double tap) finds it paid and changes nothing. The TrxID is unique, so one bKash
 *   transaction can never pay two payments.
 * - Money that moved but cannot be matched is NOT granted. It stays as an event for staff ("attention").
 * - Events and logs hold short codes only: never a token, a phone number or a bKash response body.
 */
final class SettleBkashPayment
{
    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const PENDING = 'pending';

    public const ATTENTION = 'attention';

    public const IGNORED = 'ignored';

    public function __construct(
        private readonly BkashClient $client,
        private readonly FulfilPayment $fulfil,
        private readonly PaymentEventRecorder $events,
        private readonly Notifier $notifier,
    ) {}

    /** The member came back to us. $status is what the address said: success, failure or cancel. */
    public function fromCallback(User $user, string $paymentId, string $status): string
    {
        $payment = Payment::query()
            ->where('gateway', PaymentGateway::Bkash->value)
            ->where('gateway_payment_id', $paymentId)
            ->where('user_id', $user->getKey())
            ->first();

        if ($payment === null) {
            return self::IGNORED;
        }

        if ($status === 'cancel' || $status === 'failure') {
            return $this->close((int) $payment->getKey(), $status === 'cancel');
        }

        return $this->execute((int) $payment->getKey(), $paymentId);
    }

    /**
     * Settles a payment from a bKash answer that says "Completed" (used by the Execute step here and,
     * later, by the Query step). Returns one of the outcome constants.
     *
     * @param  array<string, mixed>  $response
     */
    public function settle(int $paymentId, array $response, string $step): string
    {
        if (! BkashClient::isCompleted($response)) {
            $code = $response['statusCode'] ?? null;
            $payment = Payment::query()->find($paymentId);

            if ($payment !== null) {
                $this->events->record($payment, 'not_completed', ['step' => $step, 'code' => is_scalar($code) ? substr((string) $code, 0, 10) : null]);
            }

            return self::PENDING;
        }

        try {
            $outcome = DB::transaction(fn (): string => $this->markPaid($paymentId, $response, $step));
        } catch (UniqueConstraintViolationException) {
            // Two requests raced for the same bKash transaction. A person must look: flag it and tell staff.
            Log::warning('bkash.settle_ref_taken', ['payment_id' => $paymentId]);
            $this->flagAfterRollback($paymentId, $step, 'ref_taken');

            return self::ATTENTION;
        } catch (Throwable) {
            // Money moved but we could not record it. The payment stays as it is and the scheduled
            // reconcile asks bKash again (the "settle_failed" event makes it a candidate).
            Log::error('bkash.settle_failed', ['payment_id' => $paymentId]);
            $this->noteSettleFailed($paymentId, $step);

            return self::ATTENTION;
        }

        if ($outcome === self::ATTENTION) {
            $this->tellStaffOnce($paymentId);
        }

        if ($outcome === self::PAID) {
            $payment = Payment::query()->find($paymentId);

            if ($payment !== null && $payment->fulfilled_at !== null) {
                $this->notifier->paymentDecided($payment, NotificationType::PaymentApproved);
            }
        }

        return $outcome;
    }

    /** A payment whose saving kept failing for too long: flag it for staff (the reconcile run calls this). Never throws. */
    public function flagStuck(int $paymentId): void
    {
        $this->flagAfterRollback($paymentId, 'query', 'settle_failed_stuck');
    }

    /** After a rolled-back transaction: write the "needs attention" flag and tell staff once. Never throws. */
    private function flagAfterRollback(int $paymentId, string $step, string $why): void
    {
        try {
            $payment = Payment::query()->find($paymentId);

            if ($payment === null) {
                return;
            }

            $this->events->record($payment, 'needs_attention', ['step' => $step, 'why' => $why]);
            $this->tellStaffOnce($paymentId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * After a rolled-back transaction: remember that saving failed, once, so the reconcile run
     * asks bKash again. Not a staff flag: a short database problem usually heals by itself.
     * Never throws.
     */
    private function noteSettleFailed(int $paymentId, string $step): void
    {
        try {
            $payment = Payment::query()->find($paymentId);

            if ($payment === null || PaymentEvent::query()->where('payment_id', $paymentId)->where('event_type', 'settle_failed')->exists()) {
                return;
            }

            $this->events->record($payment, 'settle_failed', ['step' => $step]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Staff are told when a payment is flagged for the FIRST time. The same payment can come
     * back here (second tap on the callback, the next query round), but it is flagged once
     * per payment in the staff notification: more than one flag event means they were told.
     */
    private function tellStaffOnce(int $paymentId): void
    {
        $flags = PaymentEvent::query()
            ->where('payment_id', $paymentId)
            ->where('event_type', 'needs_attention')
            ->count();

        if ($flags === 1) {
            $this->notifier->toStaff(NotificationType::StaffPaymentAttention);
        }
    }

    private function execute(int $id, string $bkashPaymentId): string
    {
        $payment = Payment::query()->find($id);

        if ($payment === null) {
            return self::IGNORED;
        }

        if ($payment->status !== PaymentStatus::Pending) {
            // Paid already (second tap), or closed earlier. No call to bKash, so no money can move now.
            if ($payment->status !== PaymentStatus::Paid) {
                $this->events->record($payment, 'callback_ignored', ['status' => $payment->status->value]);
            }

            return $this->outcomeFor($payment->status);
        }

        try {
            $response = $this->client->executePayment($bkashPaymentId);
        } catch (BkashOutcomeUnknownException $e) {
            $this->events->record($payment, 'execute_unknown', ['reason' => $e->getMessage()]);

            return self::PENDING;
        } catch (BkashException $e) {
            // bKash refused before processing anything. The payment stays pending.
            $this->events->record($payment, 'gateway_error', ['step' => 'execute', 'reason' => $e->getMessage(), 'code' => $e->bkashCode]);

            return self::PENDING;
        }

        return $this->settle($id, $response, 'execute');
    }

    /** @param  array<string, mixed>  $response */
    private function markPaid(int $id, array $response, string $step): string
    {
        $payment = Payment::query()->whereKey($id)->lockForUpdate()->first();

        if ($payment === null) {
            return self::IGNORED;
        }

        if ($payment->status === PaymentStatus::Paid) {
            return self::PAID;
        }

        // Money moved at bKash. A payment that was cancelled meanwhile (stale clean-up) is still honoured.
        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Cancelled], true)) {
            $this->events->record($payment, 'needs_attention', ['step' => $step, 'why' => 'status_'.$payment->status->value]);
            Log::warning('bkash.needs_attention', ['payment' => $payment->code, 'why' => 'status']);

            return self::ATTENTION;
        }

        $problems = $this->mismatches($payment, $response);

        if ($problems !== []) {
            $this->events->record($payment, 'needs_attention', ['step' => $step, 'why' => implode(',', $problems)]);
            Log::warning('bkash.needs_attention', ['payment' => $payment->code, 'why' => implode(',', $problems)]);

            return self::ATTENTION;
        }

        $trx = (string) $response['trxID'];

        $taken = Payment::query()
            ->where('ref_scope', $payment->gateway->refScope())
            ->where('gateway_ref', $trx)
            ->whereKeyNot($payment->getKey())
            ->exists();

        if ($taken) {
            $this->events->record($payment, 'needs_attention', ['step' => $step, 'why' => 'ref_taken']);
            Log::warning('bkash.needs_attention', ['payment' => $payment->code, 'why' => 'ref_taken']);

            return self::ATTENTION;
        }

        $payment->forceFill([
            'gateway_ref' => $trx,
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
            'failure_reason' => null,
        ])->save();

        ($this->fulfil)($payment);

        $this->events->record($payment, 'paid', ['by' => 'gateway', 'step' => $step]);

        activity('payment')
            ->withProperties([
                'payment_code' => $payment->code,
                'plan_code' => $payment->plan_code,
                'amount_minor' => $payment->amount_minor,
            ])
            ->log('payment.paid_gateway');

        return self::PAID;
    }

    /**
     * The checks between bKash's answer and our own row. Returns short codes of what did not match.
     *
     * @param  array<string, mixed>  $response
     * @return list<string>
     */
    private function mismatches(Payment $payment, array $response): array
    {
        $problems = [];

        if (($response['paymentID'] ?? null) !== $payment->gateway_payment_id) {
            $problems[] = 'payment_id';
        }

        if (($response['merchantInvoiceNumber'] ?? null) !== $payment->tran_id) {
            $problems[] = 'invoice';
        }

        if (($response['currency'] ?? null) !== $payment->currency) {
            $problems[] = 'currency';
        }

        if (self::toMinor($response['amount'] ?? null) !== (int) $payment->amount_minor) {
            $problems[] = 'amount';
        }

        $trx = $response['trxID'] ?? null;

        if (! is_string($trx) || preg_match('/^[A-Za-z0-9]{6,32}$/', $trx) !== 1) {
            $problems[] = 'trx';
        }

        return $problems;
    }

    /** "750", "750.0" and "750.00" become 75000; anything else (a float string with exponent, text) becomes null. */
    private static function toMinor(mixed $value): ?int
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        if (preg_match('/^(\d{1,9})(?:\.(\d{1,2}))?$/', (string) $value, $m) !== 1) {
            return null;
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    /** The member pressed cancel at bKash, or bKash said the checkout failed. No Execute was called. */
    private function close(int $id, bool $cancelled): string
    {
        return DB::transaction(function () use ($id, $cancelled): string {
            $payment = Payment::query()->whereKey($id)->lockForUpdate()->first();

            if ($payment === null) {
                return self::IGNORED;
            }

            if ($payment->status !== PaymentStatus::Pending) {
                return $this->outcomeFor($payment->status);
            }

            $status = $cancelled ? PaymentStatus::Cancelled : PaymentStatus::Failed;

            $payment->forceFill([
                'status' => $status->value,
                'failure_reason' => $cancelled ? 'bkash_cancelled' : 'bkash_failed',
            ])->save();

            $this->events->record($payment, $cancelled ? 'cancelled' : 'failed', ['by' => 'gateway']);

            return $this->outcomeFor($status);
        });
    }

    private function outcomeFor(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Paid => self::PAID,
            PaymentStatus::Failed => self::FAILED,
            PaymentStatus::Cancelled => self::CANCELLED,
            default => self::PENDING,
        };
    }
}

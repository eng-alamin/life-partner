<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\NotificationType;
use App\Services\Notification\Notifier;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\Payment\ProofImageProcessor;
use App\Support\Digits;
use App\Support\TrxId;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The member says "I sent the money": they give the bKash TrxID, a screenshot, or
 * both (at least one), and the number they sent from. The payment then waits for
 * staff (under_review). Nothing is granted here: only staff mark it paid (Module 8.3).
 *
 * - The payment is found among the member's OWN payments by its code.
 * - A TrxID can belong to one payment only. A friendly check runs first; the
 *   database unique key is the final guard, also against the bKash API later.
 * - A screenshot is decoded and saved again as a new JPEG on a private disk.
 * - The payer number, the TrxID and the screenshot are never written to the
 *   activity log or to the event payload.
 */
final class SubmitManualPayment
{
    public function __construct(
        private readonly ProofImageProcessor $images,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(
        User $user,
        string $paymentCode,
        ?string $trxId,
        ?string $payerNumber,
        ?UploadedFile $proof = null,
    ): Payment {
        $trx = $this->cleanTrxId($trxId);
        $number = $this->cleanNumber($payerNumber);

        if ($trx === null && $proof === null) {
            throw ValidationException::withMessages(['trx_id' => __('billing.errors.proof_required')]);
        }

        // Cheap early look; the real checks run again under row locks below.
        $early = Payment::query()
            ->where('code', $paymentCode)
            ->where('user_id', $user->getKey())
            ->firstOrFail();

        Gate::forUser($user)->authorize('manage', $early);

        if ($early->status !== PaymentStatus::Pending || $early->gateway !== PaymentGateway::ManualBkash) {
            throw ValidationException::withMessages(['form' => __('billing.errors.not_payable')]);
        }

        $bytes = $proof !== null ? $this->images->process($proof) : null;

        $path = null;

        if ($bytes !== null) {
            // A new name for every attempt, so a failed attempt can never touch an older file.
            $path = $early->code.'-'.Str::lower(Str::random(12)).'.jpg';
            Storage::disk('payment_proofs')->put($path, $bytes);
        }

        try {
            $payment = DB::transaction(fn (): Payment => $this->submit($user, $paymentCode, $trx, $number, $path));
        } catch (UniqueConstraintViolationException) {
            $this->discard($path);

            throw ValidationException::withMessages(['trx_id' => __('billing.errors.trx_used')]);
        } catch (Throwable $exception) {
            $this->discard($path);

            throw $exception;
        }

        // Staff with the payments permission learn that a payment is waiting.
        $this->notifier->toStaff(NotificationType::StaffPaymentWaiting);

        return $payment;
    }

    private function submit(User $user, string $paymentCode, ?string $trx, string $number, ?string $path): Payment
    {
        User::query()->whereKey($user->getKey())->lockForUpdate()->first();

        $payment = Payment::query()
            ->where('code', $paymentCode)
            ->where('user_id', $user->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        Gate::forUser($user)->authorize('manage', $payment);

        if ($payment->status !== PaymentStatus::Pending || $payment->gateway !== PaymentGateway::ManualBkash) {
            throw ValidationException::withMessages(['form' => __('billing.errors.not_payable')]);
        }

        $waiting = Payment::query()
            ->where('user_id', $user->getKey())
            ->where('status', PaymentStatus::UnderReview->value)
            ->count();

        if ($waiting >= (int) config('lifepartner.billing.max_under_review')) {
            throw ValidationException::withMessages(['form' => __('billing.errors.too_many_waiting')]);
        }

        if ($trx !== null) {
            $taken = Payment::query()
                ->where('ref_scope', PaymentGateway::ManualBkash->refScope())
                ->where('gateway_ref', $trx)
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages(['trx_id' => __('billing.errors.trx_used')]);
            }
        }

        $payment->forceFill([
            'gateway_ref' => $trx,
            'payer_number' => $number,
            'proof_path' => $path,
            'status' => PaymentStatus::UnderReview->value,
            'submitted_at' => now(),
        ])->save();

        $event = new PaymentEvent;
        $event->forceFill([
            'payment_id' => $payment->getKey(),
            'gateway' => PaymentGateway::ManualBkash->value,
            'event_type' => 'submitted',
            'payload' => ['has_trx_id' => $trx !== null, 'has_proof' => $path !== null],
            'processed_at' => now(),
        ])->save();

        activity('payment')
            ->causedBy($user)
            ->withProperties([
                'payment_code' => $payment->code,
                'plan_code' => $payment->plan_code,
                'amount_minor' => $payment->amount_minor,
                'has_proof' => $path !== null,
            ])
            ->log('payment.submitted');

        return $payment;
    }

    /** Upper case, no spaces. A bKash TrxID is 8 to 12 letters and digits. */
    private function cleanTrxId(?string $value): ?string
    {
        return TrxId::clean($value);
    }

    /** Bangladeshi mobile number: 01XXXXXXXXX. Accepts +880 and 880 in front, spaces and dashes. */
    private function cleanNumber(?string $value): string
    {
        $text = preg_replace('/[\s\-]+/u', '', (string) Digits::normalize($value)) ?? '';
        $text = preg_replace('/^(\+?880)/', '0', $text) ?? '';

        if (preg_match('/^01[3-9][0-9]{8}$/', $text) !== 1) {
            throw ValidationException::withMessages(['payer_number' => __('billing.errors.number_invalid')]);
        }

        return $text;
    }

    private function discard(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('payment_proofs')->delete($path);
        }
    }
}

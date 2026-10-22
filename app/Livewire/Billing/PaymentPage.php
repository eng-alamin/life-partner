<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Actions\Payment\CancelPayment;
use App\Actions\Payment\SubmitManualPayment;
use App\Enums\PaymentGateway;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * One manual bKash payment: how to pay, and the form where the member gives the
 * TrxID and/or a screenshot. The payment is looked up among the signed-in member's
 * own payments only, so a code of someone else gives a 404. Every input is untrusted;
 * the actions check everything again on the server.
 */
#[Layout('layouts::app')]
class PaymentPage extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $code = '';

    public string $trxId = '';

    public string $payerNumber = '';

    /** The chosen screenshot (a Livewire temporary upload). */
    public $proof = null;

    public function mount(string $payment): void
    {
        $this->code = $this->find($payment)->code;
    }

    public function submit(SubmitManualPayment $submit): void
    {
        $this->resetErrorBag();
        $this->throttle();

        $this->validate([
            'proof' => ['nullable', 'file', 'max:'.(int) config('lifepartner.billing.proof_max_kb'), 'mimes:jpg,jpeg,png,webp'],
        ], [
            'proof.file' => __('billing.errors.proof_unreadable'),
            'proof.uploaded' => __('billing.errors.proof_upload_failed'),
            'proof.max' => __('billing.errors.proof_too_big'),
            'proof.mimes' => __('billing.errors.proof_type'),
        ]);

        $submit(auth()->user(), $this->code, $this->trxId, $this->payerNumber, $this->proof);

        $this->reset(['trxId', 'payerNumber', 'proof']);
    }

    public function cancel(CancelPayment $cancel): void
    {
        $this->resetErrorBag();
        $this->throttle();

        $cancel(auth()->user(), $this->code);
    }

    public function render()
    {
        $payment = $this->find($this->code);

        return view('livewire.billing.payment-page', [
            'payment' => $payment,
            'amount' => Money::format($payment->amount_minor, $payment->currency),
            'bkashNumber' => (string) config('lifepartner.billing.bkash_number'),
            'proofMaxMb' => max(1, intdiv((int) config('lifepartner.billing.proof_max_kb'), 1024)),
        ])->title(__('billing.pay.title'));
    }

    private function find(string $code): Payment
    {
        return Payment::query()
            ->where('code', $code)
            ->where('user_id', auth()->id())
            ->where('gateway', PaymentGateway::ManualBkash->value)
            ->firstOrFail();
    }

    private function throttle(): void
    {
        $key = 'payment-submit:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}

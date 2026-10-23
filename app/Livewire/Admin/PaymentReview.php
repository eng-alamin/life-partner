<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Payment\ApprovePayment;
use App\Actions\Payment\RefundPayment;
use App\Actions\Payment\RejectPayment;
use App\Actions\Payment\ResolveBkashAttention;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One payment for staff. The payment code is locked (the browser cannot change it);
 * every decision is checked again on the server inside the action, which also
 * locks the payment row.
 */
#[Layout('layouts::admin')]
class PaymentReview extends Component
{
    #[Locked]
    public string $paymentCode = '';

    /** Needed only when the member gave a screenshot but no TrxID. */
    public string $trxId = '';

    public string $reason = '';

    public bool $removeCredits = false;

    public bool $cancelSubscription = false;

    public function mount(Payment $payment): void
    {
        Gate::authorize('review', $payment);

        $this->paymentCode = $payment->code;
    }

    public function approve(ApprovePayment $approve): void
    {
        $this->begin();

        $approve(auth()->user(), $this->paymentCode, $this->trxId);

        $this->finish('approved');
    }

    public function reject(RejectPayment $reject): void
    {
        $this->begin();

        $reject(auth()->user(), $this->paymentCode, $this->reason);

        $this->finish('rejected');
    }

    public function refund(RefundPayment $refund): void
    {
        $this->begin();

        $refund(auth()->user(), $this->paymentCode, $this->removeCredits, $this->cancelSubscription);

        $this->finish('refunded');
    }

    public function resolveAttention(ResolveBkashAttention $resolve): void
    {
        $this->begin();

        $resolve(auth()->user(), $this->paymentCode);

        $this->finish('attention_resolved');
    }

    public function render()
    {
        $payment = Payment::query()
            ->with(['events', 'subscription', 'user.biodata:id,user_id,public_code'])
            ->where('code', $this->paymentCode)
            ->firstOrFail();

        Gate::authorize('review', $payment);

        return view('livewire.admin.payment-review', [
            'payment' => $payment,
            'amount' => Money::format($payment->amount_minor, $payment->currency),
            'memberCode' => $payment->user?->biodata?->public_code,
            'canDecide' => $payment->status === PaymentStatus::UnderReview && $payment->gateway->isManual(),
            'canRefund' => $payment->status === PaymentStatus::Paid,
            'needsTrx' => $payment->gateway_ref === null,
            'hasProof' => $payment->proof_path !== null,
            'needsAttention' => $payment->gateway === PaymentGateway::Bkash
                && ! in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true)
                && $payment->events->contains('event_type', 'needs_attention')
                && ! $payment->events->contains('event_type', 'attention_resolved'),
        ])->title(__('admin_payments.review.title'));
    }

    private function begin(): void
    {
        $this->resetErrorBag();

        $key = 'payment-review:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function finish(string $outcome): void
    {
        session()->flash('status', __('admin_payments.flash.'.$outcome));
        $this->redirectRoute('admin.payments');
    }
}

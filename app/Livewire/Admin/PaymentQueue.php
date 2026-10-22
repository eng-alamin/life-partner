<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Payments for staff. "Review" lists payments waiting for a decision, oldest first;
 * "decided" lists paid, rejected and refunded payments, newest first. The list shows
 * only the payment code, plan, amount, the member's biodata code and whether proof
 * was given. The payer number, the TrxID and the screenshot are read on the payment page.
 */
#[Layout('layouts::admin')]
class PaymentQueue extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    private const TABS = ['review', 'decided'];

    #[Url(as: 'tab')]
    public string $tab = 'review';

    public function mount(): void
    {
        Gate::authorize('viewAny', Payment::class);

        $this->normalizeTab();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('viewAny', Payment::class);

        $this->tab = $tab;
        $this->normalizeTab();
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('viewAny', Payment::class);

        $this->normalizeTab();

        $query = Payment::query()
            ->select(['id', 'code', 'user_id', 'plan_name', 'plan_kind', 'amount_minor', 'currency', 'status', 'gateway_ref', 'proof_path', 'submitted_at', 'reviewed_at'])
            ->with('user.biodata:id,user_id,public_code');

        if ($this->tab === 'review') {
            $query->where('status', PaymentStatus::UnderReview->value)->orderBy('submitted_at')->orderBy('id');
        } else {
            $query->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Failed->value, PaymentStatus::Refunded->value])
                ->orderByDesc('reviewed_at')
                ->orderByDesc('id');
        }

        $items = $query->simplePaginate(self::PER_PAGE);

        $rows = $items->getCollection()->map(fn (Payment $payment): array => [
            'code' => $payment->code,
            'plan' => $payment->plan_name,
            'amount' => Money::format($payment->amount_minor, $payment->currency),
            'status' => $payment->status,
            'member' => $payment->user?->biodata?->public_code,
            'has_trx' => $payment->gateway_ref !== null,
            'has_proof' => $payment->proof_path !== null,
            'when' => ($this->tab === 'review' ? $payment->submitted_at : $payment->reviewed_at)?->diffForHumans(),
        ])->all();

        return view('livewire.admin.payment-queue', [
            'items' => $items,
            'rows' => $rows,
        ])->title(__('admin_payments.queue.title'));
    }

    private function normalizeTab(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'review';
        }
    }
}

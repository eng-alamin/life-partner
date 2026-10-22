<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Credit\CreditBalance;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The member's own credits, membership and payments. Everything is read for the
 * signed-in member only.
 */
#[Layout('layouts::app')]
class BillingIndex extends Component
{
    private const MAX_ROWS = 30;

    public function render(CreditBalance $balance)
    {
        $userId = auth()->id();

        $membership = Subscription::query()
            ->where('user_id', $userId)
            ->where('status', SubscriptionStatus::Active->value)
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->first();

        $payments = Payment::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(self::MAX_ROWS)
            ->get()
            ->map(fn (Payment $payment): array => [
                'code' => $payment->code,
                'plan' => $payment->plan_name,
                'amount' => Money::format($payment->amount_minor, $payment->currency),
                'status' => $payment->status,
                'date' => $payment->created_at,
                'open' => in_array($payment->status->value, ['pending', 'under_review'], true),
            ])->all();

        return view('livewire.billing.billing-index', [
            'credits' => $balance->for(auth()->user()),
            'membershipEnds' => $membership?->ends_at,
            'payments' => $payments,
        ])->title(__('billing.index.title'));
    }
}

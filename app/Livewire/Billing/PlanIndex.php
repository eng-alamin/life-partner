<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Actions\Payment\StartBkashPayment;
use App\Actions\Payment\StartCheckout;
use App\Models\Plan;
use App\Services\Payment\BkashClient;
use App\Support\Money;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Public list of active plans. Guests can read prices; buying needs a login, a
 * verified email and an approved biodata (StartCheckout checks all of it on the
 * server). The plan is chosen by its code and the price is read from our database.
 */
#[Layout('layouts::browse')]
class PlanIndex extends Component
{
    public function buy(StartCheckout $start, string $code): void
    {
        $user = auth()->user();

        if ($user === null) {
            $this->redirectRoute('login');

            return;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->redirectRoute('verification.notice');

            return;
        }

        $this->resetErrorBag();
        $this->throttle();

        $payment = $start($user, $code);

        $this->redirectRoute('billing.pay', ['payment' => $payment->code]);
    }

    /** Pay through the bKash API: the member is sent to the bKash page and comes back to the callback route. */
    public function buyWithBkash(StartBkashPayment $start, string $code): void
    {
        $user = auth()->user();

        if ($user === null) {
            $this->redirectRoute('login');

            return;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->redirectRoute('verification.notice');

            return;
        }

        $this->resetErrorBag();
        $this->throttle();

        $url = $start($user, $code, route('billing.bkash.callback'));

        $this->redirect($url);
    }

    public function render(BkashClient $bkash)
    {
        $plans = Plan::query()->active()->get()->map(fn (Plan $plan): array => [
            'code' => $plan->code,
            'name' => $plan->name(),
            'description' => $plan->description(),
            'kind' => $plan->kind,
            'price' => Money::format($plan->price_minor, $plan->currency),
            'priceMinor' => $plan->price_minor,
            'credits' => $plan->credits,
            'days' => $plan->duration_days,
            'dailyLimit' => $plan->daily_interest_limit,
        ])->all();

        return view('livewire.billing.plan-index', [
            'plans' => $plans,
            'configured' => trim((string) config('lifepartner.billing.bkash_number')) !== '',
            'bkashApi' => $bkash->isEnabled(),
        ])->title(__('billing.plans.title'));
    }

    private function throttle(): void
    {
        $key = 'checkout:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}

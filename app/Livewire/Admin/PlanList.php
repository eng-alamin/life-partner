<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Plan\SetPlanActive;
use App\Models\Plan;
use App\Support\Digits;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * All plans for the admin, shown and hidden with one button. Plans are never deleted.
 * The code sent by the browser is only a lookup key; SetPlanActive authorizes again.
 */
#[Layout('layouts::admin')]
class PlanList extends Component
{
    #[Locked]
    public ?string $notice = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Plan::class);
    }

    public function setActive(SetPlanActive $set, string $code, bool $active): void
    {
        Gate::authorize('viewAny', Plan::class);

        $this->resetErrorBag();
        $this->notice = null;
        $this->throttle();

        $set(auth()->user(), $code, $active);

        $this->notice = __($active ? 'admin_plans.flash.activated' : 'admin_plans.flash.deactivated');
    }

    public function render()
    {
        Gate::authorize('viewAny', Plan::class);

        $rows = Plan::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->map(fn (Plan $plan): array => [
                'code' => $plan->code,
                'name' => $plan->name(),
                'kind' => $plan->kind,
                'price' => Money::format($plan->price_minor, $plan->currency),
                'credits' => $plan->credits,
                'days' => $plan->duration_days,
                'limit' => $plan->daily_interest_limit,
                'sort' => Digits::localize($plan->sort_order),
                'active' => $plan->is_active,
            ])->all();

        return view('livewire.admin.plan-list', ['rows' => $rows])->title(__('admin_plans.list.title'));
    }

    private function throttle(): void
    {
        $key = 'plan-admin:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}

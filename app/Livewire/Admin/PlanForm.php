<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Plan\SavePlan;
use App\Enums\PlanKind;
use App\Models\Plan;
use App\Support\TakaInput;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One form for creating and editing a plan. When editing, the plan code is locked
 * and the code and kind sent by the browser are ignored: SavePlan reads them from
 * the database. Every value is checked again in SavePlan.
 */
#[Layout('layouts::admin')]
class PlanForm extends Component
{
    #[Locked]
    public ?string $planCode = null;

    public string $code = '';

    public string $kind = 'credit_pack';

    public string $name_bn = '';

    public string $name_en = '';

    public string $description_bn = '';

    public string $description_en = '';

    /** Taka, as typed: "500" or "499.50". */
    public string $price = '';

    public string $credits = '';

    public string $duration_days = '';

    public string $daily_interest_limit = '';

    public string $sort_order = '0';

    public bool $is_active = false;

    public function mount(?string $plan = null): void
    {
        if ($plan === null) {
            Gate::authorize('create', Plan::class);

            return;
        }

        $model = Plan::query()->where('code', $plan)->firstOrFail();

        Gate::authorize('update', $model);

        $this->planCode = $model->code;
        $this->code = $model->code;
        $this->kind = $model->kind->value;
        $this->name_bn = $model->name_bn;
        $this->name_en = $model->name_en;
        $this->description_bn = (string) $model->description_bn;
        $this->description_en = (string) $model->description_en;
        $this->price = TakaInput::toField($model->price_minor);
        $this->credits = (string) $model->credits;
        $this->duration_days = $model->duration_days === null ? '' : (string) $model->duration_days;
        $this->daily_interest_limit = $model->daily_interest_limit === null ? '' : (string) $model->daily_interest_limit;
        $this->sort_order = (string) $model->sort_order;
        $this->is_active = $model->is_active;
    }

    public function save(SavePlan $save): void
    {
        Gate::authorize($this->planCode === null ? 'create' : 'update', $this->planCode === null ? Plan::class : Plan::query()->where('code', $this->planCode)->firstOrFail());

        $this->resetErrorBag();
        $this->throttle();

        $plan = $save(auth()->user(), $this->planCode, $this->only([
            'code', 'kind', 'name_bn', 'name_en', 'description_bn', 'description_en',
            'price', 'credits', 'duration_days', 'daily_interest_limit', 'sort_order', 'is_active',
        ]));

        session()->flash('status', __($this->planCode === null ? 'admin_plans.flash.created' : 'admin_plans.flash.updated', ['name' => $plan->name_en]));
        $this->redirectRoute('admin.plans');
    }

    public function render()
    {
        $isEdit = $this->planCode !== null;

        return view('livewire.admin.plan-form', [
            'isEdit' => $isEdit,
            'kindOptions' => PlanKind::options(),
            'isMembership' => $this->kind === PlanKind::Membership->value,
        ])->title(__($isEdit ? 'admin_plans.form.title_edit' : 'admin_plans.form.title_create'));
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

<?php

declare(strict_types=1);

namespace App\Actions\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Shows or hides a plan on the public /plans page. A plan can only be shown while
 * its price is at least 1 poisha. Nothing is deleted: payments already made keep
 * their own saved copy of the plan.
 */
final class SetPlanActive
{
    public function __invoke(User $staff, string $planCode, bool $active): Plan
    {
        $early = Plan::query()->where('code', $planCode)->firstOrFail();
        Gate::forUser($staff)->authorize('update', $early);

        return DB::transaction(function () use ($staff, $planCode, $active): Plan {
            $plan = Plan::query()->where('code', $planCode)->lockForUpdate()->firstOrFail();

            Gate::forUser($staff)->authorize('update', $plan);

            if ($plan->is_active === $active) {
                return $plan;
            }

            if ($active && $plan->price_minor < 1) {
                throw ValidationException::withMessages(['form' => __('admin_plans.errors.cannot_activate')]);
            }

            $plan->forceFill(['is_active' => $active])->save();

            activity('plan')
                ->causedBy($staff)
                ->performedOn($plan)
                ->withProperties(['plan_code' => $plan->code, 'price_minor' => $plan->price_minor])
                ->log($active ? 'plan.activated' : 'plan.deactivated');

            return $plan;
        });
    }
}

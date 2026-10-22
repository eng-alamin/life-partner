<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\Plan;
use App\Models\User;

/**
 * Plans and prices are changed by an active admin holding plans.manage only.
 * There is no delete: a plan that was ever paid for is deactivated, never removed.
 */
class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, Plan $plan): bool
    {
        return $this->allowed($user);
    }

    private function allowed(User $user): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ManagePlans->value);
    }
}

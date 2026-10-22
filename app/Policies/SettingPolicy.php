<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\Setting;
use App\Models\User;

/**
 * Site settings are changed by an active admin holding settings.manage only.
 * There is no create or delete: the list of settings is fixed in code.
 */
class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, Setting $setting): bool
    {
        return $this->allowed($user);
    }

    private function allowed(User $user): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ManageSettings->value);
    }
}

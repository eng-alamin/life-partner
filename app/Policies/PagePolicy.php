<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\Page;
use App\Models\User;

/**
 * Page content is changed by an active admin holding pages.manage only.
 * There is no create or delete: the list of pages is fixed in code.
 */
class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, Page $page): bool
    {
        return $this->allowed($user);
    }

    private function allowed(User $user): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ManagePages->value);
    }
}

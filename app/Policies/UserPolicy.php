<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Enums\StaffRole;
use App\Enums\UserStatus;
use App\Models\User;

/**
 * Account management belongs to an active admin holding users.manage.
 *
 * Safety rules that hold even for an admin:
 * - nobody changes their own account here (no self-suspension, no self role change);
 * - a staff account is never suspended: its role is taken away first;
 * - the web gives and takes the moderator role only. The admin role is given and taken
 *   only with the lifepartner:grant-staff command, so no web form can create an admin.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->allowed($actor);
    }

    public function view(User $actor, User $target): bool
    {
        return $this->allowed($actor);
    }

    public function suspend(User $actor, User $target): bool
    {
        return $this->allowed($actor)
            && ! $actor->is($target)
            && $target->isActive()
            && ! $this->hasStaffRole($target);
    }

    public function reactivate(User $actor, User $target): bool
    {
        return $this->allowed($actor)
            && ! $actor->is($target)
            && $target->status === UserStatus::Suspended;
    }

    public function makeModerator(User $actor, User $target): bool
    {
        return $this->allowed($actor)
            && ! $actor->is($target)
            && $target->isActive()
            && $target->hasVerifiedEmail()
            && ! $this->hasStaffRole($target);
    }

    public function removeModerator(User $actor, User $target): bool
    {
        return $this->allowed($actor)
            && ! $actor->is($target)
            && $target->hasRole(StaffRole::Moderator->value);
    }

    private function allowed(User $actor): bool
    {
        return $actor->isActive()
            && $actor->checkPermissionTo(StaffPermission::ManageUsers->value);
    }

    private function hasStaffRole(User $target): bool
    {
        return $target->roles()->exists();
    }
}

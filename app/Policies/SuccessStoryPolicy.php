<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\SuccessStory;
use App\Models\User;

/**
 * Success stories are managed by an active account holding stories.manage (admin and moderator).
 * Members can not write, submit or see drafts.
 */
class SuccessStoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, SuccessStory $story): bool
    {
        return $this->allowed($user);
    }

    public function delete(User $user, SuccessStory $story): bool
    {
        return $this->allowed($user);
    }

    private function allowed(User $user): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ManageStories->value);
    }
}

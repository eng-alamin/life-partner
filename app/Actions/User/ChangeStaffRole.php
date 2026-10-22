<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Gives or takes the moderator role. The admin role is never changed here: it is given
 * and taken only with the lifepartner:grant-staff command (see UserPolicy).
 *
 * The roles must already exist (RolesAndPermissionsSeeder). This action never creates them,
 * so a missing seeder run gives a clear message instead of a half-made role.
 */
final class ChangeStaffRole
{
    public function makeModerator(User $staff, int $targetId): User
    {
        return $this->change($staff, $targetId, 'makeModerator', function (User $target): void {
            if (! Role::query()->where('name', StaffRole::Moderator->value)->where('guard_name', 'web')->exists()) {
                throw ValidationException::withMessages(['form' => __('admin_users.errors.roles_missing')]);
            }

            if ($target->hasRole(StaffRole::Moderator->value)) {
                throw ValidationException::withMessages(['form' => __('admin_users.errors.already_moderator')]);
            }
        }, StaffRole::Moderator->value, 'staff.granted');
    }

    public function removeModerator(User $staff, int $targetId): User
    {
        return $this->change($staff, $targetId, 'removeModerator', function (User $target): void {
            if (! $target->hasRole(StaffRole::Moderator->value)) {
                throw ValidationException::withMessages(['form' => __('admin_users.errors.not_moderator')]);
            }
        }, null, 'staff.revoked');
    }

    /** @param  callable(User): void  $precheck */
    private function change(User $staff, int $targetId, string $ability, callable $precheck, ?string $role, string $event): User
    {
        Gate::forUser($staff)->authorize('viewAny', User::class);

        return DB::transaction(function () use ($staff, $targetId, $ability, $precheck, $role, $event): User {
            $target = User::query()->whereKey($targetId)->lockForUpdate()->first();

            abort_if($target === null, 404);

            $precheck($target);

            Gate::forUser($staff)->authorize($ability, $target);

            $target->syncRoles($role === null ? [] : [$role]);

            activity('staff')
                ->causedBy($staff)
                ->performedOn($target)
                ->withProperties(['role' => $role])
                ->log($event);

            return $target;
        });
    }
}

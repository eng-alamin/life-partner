<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffPermission;
use App\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the staff roles and permissions. Safe to run again and again.
 *
 * Moderators get every permission except payments.manage, plans.manage, pages.manage,
 * users.manage, settings.manage and identity.review: only an admin may approve, reject or refund a payment, change
 * plans and prices, change the words of the public pages, suspend members and give the moderator
 * role, change the site settings (registration switch, contact details), or look at identity documents.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];

        foreach (StaffPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
            $all[] = $permission->value;
        }

        $adminOnly = [
            StaffPermission::ManagePayments->value,
            StaffPermission::ManagePlans->value,
            StaffPermission::ManagePages->value,
            StaffPermission::ManageUsers->value,
            StaffPermission::ManageSettings->value,
            StaffPermission::ReviewIdentity->value,
        ];

        foreach (StaffRole::cases() as $role) {
            $names = $role === StaffRole::Admin
                ? $all
                : array_values(array_diff($all, $adminOnly));

            Role::findOrCreate($role->value, 'web')->syncPermissions($names);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

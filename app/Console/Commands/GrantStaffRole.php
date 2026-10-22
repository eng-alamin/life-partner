<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;

/**
 * The only way to create staff: there is no default admin password and no
 * web form that can grant a role. The account must already exist, be active
 * and have a verified email.
 */
class GrantStaffRole extends Command
{
    protected $signature = 'lifepartner:grant-staff
        {email : Email of an existing account}
        {--role=admin : admin or moderator}
        {--revoke : Remove all staff roles from the account instead}';

    protected $description = 'Give an existing account a staff role (admin or moderator), or remove it.';

    public function handle(RolesAndPermissionsSeeder $seeder): int
    {
        $user = User::query()
            ->where('email', mb_strtolower(trim((string) $this->argument('email'))))
            ->first();

        if ($user === null) {
            $this->error('No account with that email exists.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            $user->syncRoles([]);
            activity('staff')->performedOn($user)->withProperties(['role' => null])->log('staff.revoked');
            $this->info('All staff roles were removed from this account.');

            return self::SUCCESS;
        }

        $role = StaffRole::tryFrom((string) $this->option('role'));

        if ($role === null) {
            $this->error('The role must be admin or moderator.');

            return self::FAILURE;
        }

        if (! $user->isActive() || ! $user->hasVerifiedEmail()) {
            $this->error('The account must be active and have a verified email.');

            return self::FAILURE;
        }

        $seeder->run();
        $user->syncRoles([$role->value]);
        activity('staff')->performedOn($user)->withProperties(['role' => $role->value])->log('staff.granted');

        $this->info("This account is now: {$role->value}.");

        return self::SUCCESS;
    }
}

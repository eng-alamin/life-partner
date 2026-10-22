<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Creates the first admin from three .env values. Run it by name; it is NOT part of db:seed:
 *
 *   php artisan db:seed --class=AdminUserSeeder
 *
 * Rules:
 * - No default email and no default password anywhere in the code. Missing or weak values stop the seeder
 *   with a clear message and change nothing.
 * - A new account is created active, with a verified email, and gets the admin role (all permissions).
 * - An account that already exists keeps its password. It is only given the admin role, and only when it
 *   is active and has a verified email (the same rule as lifepartner:grant-staff).
 * - Safe to run again: an admin that already exists is left untouched.
 * - The log keeps the role only, never the email, name or password.
 *
 * There is one admin role and it holds every permission, so there is no separate "super admin".
 * Delete LP_ADMIN_PASSWORD from .env after the first run.
 */
class AdminUserSeeder extends Seeder
{
    public const PASSWORD_MIN = 8;

    public function run(): void
    {
        $email = Str::lower(trim((string) config('lifepartner.admin.email')));
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) config('lifepartner.admin.name')));
        $password = (string) config('lifepartner.admin.password');

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Set LP_ADMIN_EMAIL in .env to a valid email address, run "php artisan config:clear", and try again.');
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing === null) {
            if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
                throw new InvalidArgumentException('Set LP_ADMIN_NAME in .env (2 to 100 characters).');
            }

            if (mb_strlen($password) < self::PASSWORD_MIN) {
                throw new InvalidArgumentException('Set LP_ADMIN_PASSWORD in .env to at least '.self::PASSWORD_MIN.' characters.');
            }
        } elseif (! $existing->isActive() || ! $existing->hasVerifiedEmail()) {
            throw new InvalidArgumentException('That account must be active and have a verified email before it can become an admin.');
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $created = false;
        $granted = false;

        DB::transaction(function () use ($email, $name, $password, $existing, &$created, &$granted): void {
            $user = $existing;

            if ($user === null) {
                // forceFill: email_verified_at is deliberately not mass-assignable. The password is hashed by the model cast.
                $user = new User;
                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'locale' => 'bn',
                    'email_verified_at' => now(),
                ])->save();
                $user->refresh(); // load DB defaults such as status = active
                $created = true;
            }

            if (! $created && $user->hasExactRoles([StaffRole::Admin->value])) {
                return;
            }

            $user->syncRoles([StaffRole::Admin->value]);
            $granted = true;

            activity('staff')->performedOn($user)->withProperties(['role' => StaffRole::Admin->value])->log('staff.granted');
        });

        if ($created) {
            $this->command?->info('Admin account created. Now delete LP_ADMIN_PASSWORD from .env and run "php artisan config:clear".');
        } elseif ($granted) {
            $this->command?->info('The existing account is now an admin. Its password was not changed.');
        } else {
            $this->command?->info('Nothing to do: that account is already an admin.');
        }
    }
}

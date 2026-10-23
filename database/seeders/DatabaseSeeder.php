<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data every environment needs.
        $this->call(GeographySeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        // Inactive sample plans with price 0. The owner sets real prices in admin.
        $this->call(PlanSeeder::class);
        // Starting text for the content pages. Legal pages stay unpublished until the owner has checked them.
        $this->call(PageSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);

        // A demo account only for local development, never in production.
        if (app()->environment('local')) {
            $user = User::query()->firstOrNew(['email' => 'test@example.com']);

            if (! $user->exists) {
                // forceFill: email_verified_at is deliberately not mass-assignable.
                $user->forceFill([
                    'name' => 'Test User',
                    'password' => 'password',
                    'locale' => 'bn',
                    'email_verified_at' => now(),
                ])->save();
            }
        }
    }
}

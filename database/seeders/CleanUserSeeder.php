<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;

/**
 * CleanUserSeeder
 *
 * Creates only the essential admin user for a clean project.
 * User credentials can be configured via .env variables.
 */
class CleanUserSeeder extends Seeder
{
    public function run(): void
    {
        // Get system user for activity logging
        $system = User::where('email', 'system@audit.local')->first();

        // Create the God user (configurable via env)
        $email = env('CLEAN_ADMIN_EMAIL', 'god@example.com');
        $name = env('CLEAN_ADMIN_NAME', 'God');
        $password = env('CLEAN_ADMIN_PASSWORD', 'supersecure');

        $god = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,  // El modelo User hashea automáticamente via cast 'hashed'
                'status' => 'active',
            ]
        );

        if (!$god->hasRole('god')) {
            $god->assignRole('god');
        }

        // Create secondary admin user (Labor Wasser default)
        $admin = User::firstOrCreate(
            ['email' => 'admin@laborwasser.com'],
            [
                'name' => 'Administrador',
                'password' => 'Admin2026!',
                'status' => 'active',
            ]
        );

        if (!$admin->hasRole('god')) {
            $admin->assignRole('god');
        }

        if ($system) {
            activity()
                ->causedBy($system)
                ->performedOn($admin)
                ->event('seeding')
                ->log("Admin user created: {$email}");
        }

        $this->command->info("  - God user created: {$email}");
        $this->command->info("  - Admin user created: admin@laborwasser.com");

        // Show password hint in non-production
        if (app()->environment('local', 'development', 'staging')) {
            $this->command->warn("    Password for both: {$password}");
            $this->command->warn("    (Change this immediately in production!)");
        }
    }
}

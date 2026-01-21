<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        // Create the God/Admin user
        $email = env('CLEAN_ADMIN_EMAIL', 'admin@laborwasser.com');
        $name = env('CLEAN_ADMIN_NAME', 'Administrador');
        $password = env('CLEAN_ADMIN_PASSWORD', 'Admin2026!');

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'status' => 'active',
            ]
        );

        // Assign god role
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

        $this->command->info("  - Admin user created: {$email}");

        // Show password hint in non-production
        if (app()->environment('local', 'development', 'staging')) {
            $this->command->warn("    Password: {$password}");
            $this->command->warn("    (Change this immediately in production!)");
        }
    }
}

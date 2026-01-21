<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * CleanDatabaseSeeder - For production/clean project initialization
 *
 * This seeder creates ONLY the essential data needed to start a new project:
 * - Roles and permissions (god, admin, tech, customer, guest)
 * - Single God user (configurable via .env)
 * - Essential catalogs (units, currencies, payment methods, etc.)
 * - NO demo data
 *
 * Usage:
 *   php artisan migrate:fresh --seeder=CleanDatabaseSeeder
 */
class CleanDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  Clean Project Initialization');
        $this->command->info('========================================');
        $this->command->info('');

        // Phase 1: Core roles and permissions
        $this->command->info('Phase 1: Roles and Permissions...');
        $this->call(CleanRolesAndPermissionsSeeder::class);

        // Phase 2: Admin user
        $this->command->info('Phase 2: Admin User...');
        $this->call(CleanUserSeeder::class);

        // Phase 3: Essential catalogs
        $this->command->info('Phase 3: Essential Catalogs...');
        $this->call(CleanCatalogsSeeder::class);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  Clean Project Ready!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('  1. Configure .env with your settings');
        $this->command->info('  2. Login with: ' . config('clean.admin_email', 'admin@laborwasser.com'));
        $this->command->info('  3. Add your company settings in dashboard');
        $this->command->info('');

        Log::info('CleanDatabaseSeeder completed successfully');
    }
}

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

        // Phase 4: SAT catalogs subset (full load: php artisan sat:sync-catalogs)
        $this->command->info('Phase 4: SAT Catalogs...');
        $this->call(\Modules\SatCatalogs\Database\Seeders\SatCatalogsSeeder::class);

        // Phase 5: Fiscal periods (Fase 2.7). Sin periodos fiscales abiertos, TODO
        // posting a GL (COGS, facturas AR/AP) falla con "No open fiscal period
        // found" y los listeners lo tragan en silencio: el ciclo contable
        // simplemente no nace en una instalacion limpia. Anio pasado + actual +
        // siguiente, todos abiertos.
        $this->command->info('Phase 5: Fiscal Periods...');
        foreach ([now()->year - 1, now()->year, now()->year + 1] as $year) {
            for ($month = 1; $month <= 12; $month++) {
                $startDate = \Carbon\Carbon::create($year, $month, 1);
                \Modules\Accounting\Models\FiscalPeriod::firstOrCreate(
                    ['year' => $year, 'month' => $month],
                    [
                        'name' => sprintf('%d-%02d', $year, $month),
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $startDate->copy()->endOfMonth()->format('Y-m-d'),
                        'status' => 'open',
                        'metadata' => [],
                    ]
                );
            }
        }

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  Clean Project Ready!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('  1. Configure .env with your settings');
        $this->command->info('  2. Login with: ' . config('clean.admin_email', 'admin@example.com'));
        $this->command->info('  3. Add your company settings in dashboard');
        $this->command->info('');

        Log::info('CleanDatabaseSeeder completed successfully');
    }
}

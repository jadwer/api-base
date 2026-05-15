<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DemoAppSettingsSeeder - Opt-in seeder for demo AppSettings.
 *
 * Why this exists: production tenants populate their own AppSettings
 * (company.name, branding.*, etc.) via their own tenant seeder (e.g.
 * AlphaLabSeeder, LaborwasserSeeder) using firstOrCreate. If CleanDatabaseSeeder
 * creates Demo Company rows first, the tenant seeder finds them and skips
 * the insert, leaving production with "Demo Company" branding (deuda B8).
 *
 * Fix: CleanCatalogsSeeder no longer calls AppSettingSeeder. The DatabaseSeeder
 * (dev/local full seed) still does, so local development is unchanged. For
 * production deploys the operator runs CleanDatabaseSeeder + the tenant
 * seeder, and the demo rows never get created. If demo values are wanted
 * explicitly (e.g. testing the dashboard with placeholder copy), invoke
 * this seeder directly:
 *
 *   php artisan db:seed --class=Database\\Seeders\\DemoAppSettingsSeeder
 *
 * This thin wrapper just delegates to the existing AppSettingSeeder.
 */
class DemoAppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Populating demo AppSettings (Demo Company / placeholder values)...');
        $this->call(\Modules\AppConfig\Database\Seeders\AppSettingSeeder::class);
        $this->command->info('  - Demo AppSettings created');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppConfig\Models\AppSetting;

/**
 * DemoAppSettingsSeeder - Opt-in seeder for demo AppSettings.
 *
 * Why this exists: production tenants populate their own AppSettings
 * (company.name, branding.*, etc.) via their own tenant seeder (e.g.
 * AlphaLabSeeder or your tenant seeder) using firstOrCreate. If CleanDatabaseSeeder
 * creates placeholder rows first, the tenant seeder finds them and skips
 * the insert, leaving production with placeholder branding (deuda B8).
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
 * Delegates to AppSettingSeeder and then applies the white-label demo
 * identity: "Tu Marca" with a generic logo, the seller's contact email from
 * DEMO_CONTACT_EMAIL (.env of the demo) and no invented phone.
 */
class DemoAppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Populating demo AppSettings (Tu Marca / placeholder values)...');
        $this->call(\Modules\AppConfig\Database\Seeders\AppSettingSeeder::class);

        AppSetting::set('company.name', 'Tu Marca');
        AppSetting::set('mail.system_name', 'Tu Marca');
        // Logo generico de "Tu Marca" (gpt, 23-10).
        foreach (['company.logo_path', 'company.logo_path_alt', 'company.logo_path_footer'] as $key) {
            AppSetting::set($key, '/images/brand/logo.png');
        }
        AppSetting::set('company.email', (string) env('DEMO_CONTACT_EMAIL', ''));
        $this->command->info('  - Demo AppSettings created');
    }
}

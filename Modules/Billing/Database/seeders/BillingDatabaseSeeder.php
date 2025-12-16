<?php

namespace Modules\Billing\Database\Seeders;

use Illuminate\Database\Seeder;

class BillingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Seeding Billing module...');

        $this->call([
            PermissionsSeeder::class,
            BillingAssignPermissionsSeeder::class,
            // ✅ DEMO DATA - Enabled for presentation
            BillingDemoDataSeeder::class, // Company settings, CFDI invoices
        ]);

        $this->command->info('🎉 Billing module seeded successfully!');
    }
}

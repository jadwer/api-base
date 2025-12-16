<?php

namespace Modules\CRM\Database\Seeders;

use Illuminate\Database\Seeder;

class CRMDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📊 Seeding CRM module...');

        $this->call([
            PermissionsSeeder::class,
            CRMAssignPermissionsSeeder::class,
            PipelineStageSeeder::class,
            // ✅ DEMO DATA - Enabled for presentation
            CRMDemoDataSeeder::class, // Campaigns, Leads, Activities, Opportunities
        ]);

        $this->command->info('🎉 CRM module seeded successfully!');
    }
}

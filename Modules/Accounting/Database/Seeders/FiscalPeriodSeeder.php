<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding FiscalPeriod...');
        
        // Create sample FiscalPeriod records
        FiscalPeriod::factory()->count(10)->create();

        // Create some active records
        FiscalPeriod::factory()->active()->count(5)->create();

        // Create some inactive records
        FiscalPeriod::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ FiscalPeriod seeded successfully!');
    }
}

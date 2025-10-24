<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ExchangeRatePolicy...');
        
        // Create sample ExchangeRatePolicy records
        ExchangeRatePolicy::factory()->count(10)->create();

        
        $this->command->info('✅ ExchangeRatePolicy seeded successfully!');
    }
}

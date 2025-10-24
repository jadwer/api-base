<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ExchangeRate...');
        
        // Create sample ExchangeRate records
        ExchangeRate::factory()->count(10)->create();

        // Create some active records
        ExchangeRate::factory()->active()->count(5)->create();

        // Create some inactive records
        ExchangeRate::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ ExchangeRate seeded successfully!');
    }
}

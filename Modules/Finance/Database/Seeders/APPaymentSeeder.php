<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\APPayment;

class APPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding APPayment...');
        
        // Create sample APPayment records
        APPayment::factory()->count(10)->create();

        // Create some active records
        APPayment::factory()->active()->count(5)->create();

        // Create some inactive records
        APPayment::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ APPayment seeded successfully!');
    }
}

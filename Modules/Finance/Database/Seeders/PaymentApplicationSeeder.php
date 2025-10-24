<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding PaymentApplication...');
        
        // Create sample PaymentApplication records
        PaymentApplication::factory()->count(10)->create();

        // Create some active records
        PaymentApplication::factory()->active()->count(5)->create();

        // Create some inactive records
        PaymentApplication::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ PaymentApplication seeded successfully!');
    }
}

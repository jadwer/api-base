<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding PaymentMethod...');
        
        // Create sample PaymentMethod records
        PaymentMethod::factory()->count(10)->create();

        // Create some active records
        PaymentMethod::factory()->active()->count(5)->create();

        // Create some inactive records
        PaymentMethod::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ PaymentMethod seeded successfully!');
    }
}

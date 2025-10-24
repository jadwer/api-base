<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\Payment;
use Modules\Sales\Models\Customer;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Payment...');
        
        // Get existing Customer records
        $customers = \Modules\Sales\Models\Customer::all();
        
        if ($customers->isEmpty()) {
            $this->command->warn('No Customer records found. Skipping customer_id seeding.');
            return;
        }

        // Create sample Payment records
        // Create Payment records using existing Customer records
        $customers->take(5)->each(function ($parent) {
            Payment::factory()
                ->count(rand(1, 3))
                ->create(['customer_id' => $parent->id]);
        });

        // Create some active records
        Payment::factory()->active()->count(5)->create();

        // Create some inactive records
        Payment::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ Payment seeded successfully!');
    }
}

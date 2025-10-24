<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\ARInvoice;
use Modules\Sales\Models\Customer;

class ARInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ARInvoice...');
        
        // Get existing Customer records
        $customers = \Modules\Sales\Models\Customer::all();
        
        if ($customers->isEmpty()) {
            $this->command->warn('No Customer records found. Skipping customer_id seeding.');
            return;
        }

        // Create sample ARInvoice records
        // Create ARInvoice records using existing Customer records
        $customers->take(5)->each(function ($parent) {
            ARInvoice::factory()
                ->count(rand(1, 3))
                ->create(['customer_id' => $parent->id]);
        });

        // Create some active records
        ARInvoice::factory()->active()->count(5)->create();

        // Create some inactive records
        ARInvoice::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ ARInvoice seeded successfully!');
    }
}

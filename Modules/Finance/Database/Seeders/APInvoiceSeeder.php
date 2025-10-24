<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\APInvoice;
use Modules\Purchase\Models\Supplier;

class APInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding APInvoice...');
        
        // Get existing Supplier records
        $suppliers = \Modules\Purchase\Models\Supplier::all();
        
        if ($suppliers->isEmpty()) {
            $this->command->warn('No Supplier records found. Skipping supplier_id seeding.');
            return;
        }

        // Create sample APInvoice records
        // Create APInvoice records using existing Supplier records
        $suppliers->take(5)->each(function ($parent) {
            APInvoice::factory()
                ->count(rand(1, 3))
                ->create(['supplier_id' => $parent->id]);
        });

        // Create some active records
        APInvoice::factory()->active()->count(5)->create();

        // Create some inactive records
        APInvoice::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ APInvoice seeded successfully!');
    }
}

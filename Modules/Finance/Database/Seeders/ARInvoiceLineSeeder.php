<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\ARInvoiceLine;

class ARInvoiceLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ARInvoiceLine...');
        
        // Create sample ARInvoiceLine records
        ARInvoiceLine::factory()->count(10)->create();

        
        $this->command->info('✅ ARInvoiceLine seeded successfully!');
    }
}

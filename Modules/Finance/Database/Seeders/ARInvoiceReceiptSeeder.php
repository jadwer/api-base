<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\ARInvoiceReceipt;

class ARInvoiceReceiptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ARInvoiceReceipt...');
        
        // Create sample ARInvoiceReceipt records
        ARInvoiceReceipt::factory()->count(10)->create();

        
        $this->command->info('✅ ARInvoiceReceipt seeded successfully!');
    }
}

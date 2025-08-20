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
        
        // Get existing invoices and receipts
        $invoices = \Modules\Finance\Models\ARInvoice::all();
        $receipts = \Modules\Finance\Models\ARReceipt::all();
        
        if ($invoices->count() > 0 && $receipts->count() > 0) {
            // Create sample ARInvoiceReceipt records using existing data
            for ($i = 0; $i < min(10, $invoices->count(), $receipts->count()); $i++) {
                ARInvoiceReceipt::factory()->create([
                    'ar_invoice_id' => $invoices->random()->id,
                    'ar_receipt_id' => $receipts->random()->id,
                ]);
            }
        }
        
        $this->command->info('✅ ARInvoiceReceipt seeded successfully!');
    }
}

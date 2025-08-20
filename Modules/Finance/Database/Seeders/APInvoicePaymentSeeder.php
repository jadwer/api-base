<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\APInvoicePayment;

class APInvoicePaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding APInvoicePayment...');
        
        // Get existing invoices and payments
        $invoices = \Modules\Finance\Models\APInvoice::all();
        $payments = \Modules\Finance\Models\APPayment::all();
        
        if ($invoices->count() > 0 && $payments->count() > 0) {
            // Create sample APInvoicePayment records using existing data
            for ($i = 0; $i < min(10, $invoices->count(), $payments->count()); $i++) {
                APInvoicePayment::factory()->create([
                    'ap_invoice_id' => $invoices->random()->id,
                    'ap_payment_id' => $payments->random()->id,
                ]);
            }
        }
        
        $this->command->info('✅ APInvoicePayment seeded successfully!');
    }
}

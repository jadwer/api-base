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
        
        // Create sample APInvoicePayment records
        APInvoicePayment::factory()->count(10)->create();

        
        $this->command->info('✅ APInvoicePayment seeded successfully!');
    }
}

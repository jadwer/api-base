<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\ARInvoice;

class ARInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ARInvoice...');
        
        // Create sample ARInvoice records
        ARInvoice::factory()->count(10)->create();

        // Create some posted records
        ARInvoice::factory()->posted()->count(5)->create();

        // Create some paid records
        ARInvoice::factory()->paid()->count(2)->create();

        // Create some draft records
        ARInvoice::factory()->draft()->count(3)->create();

        
        $this->command->info('✅ ARInvoice seeded successfully!');
    }
}

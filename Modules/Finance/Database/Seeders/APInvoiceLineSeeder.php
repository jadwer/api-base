<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\APInvoiceLine;

class APInvoiceLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding APInvoiceLine...');
        
        // Create sample APInvoiceLine records
        APInvoiceLine::factory()->count(10)->create();

        
        $this->command->info('✅ APInvoiceLine seeded successfully!');
    }
}

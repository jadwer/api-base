<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\APInvoice;

class APInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding APInvoice...');
        
        // Create sample APInvoice records
        APInvoice::factory()->count(10)->create();

        // Create some posted records
        APInvoice::factory()->posted()->count(5)->create();

        // Create some paid records
        APInvoice::factory()->paid()->count(2)->create();

        // Create some draft records
        APInvoice::factory()->draft()->count(3)->create();

        
        $this->command->info('✅ APInvoice seeded successfully!');
    }
}

<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\ARReceipt;

class ARReceiptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding ARReceipt...');
        
        // Create sample ARReceipt records
        ARReceipt::factory()->count(10)->create();

        // Create some active records
        ARReceipt::factory()->active()->count(5)->create();

        // Create some inactive records
        ARReceipt::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ ARReceipt seeded successfully!');
    }
}

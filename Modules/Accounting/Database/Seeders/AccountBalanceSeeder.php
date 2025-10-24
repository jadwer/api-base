<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding AccountBalance...');
        
        // Create sample AccountBalance records
        AccountBalance::factory()->count(10)->create();

        
        $this->command->info('✅ AccountBalance seeded successfully!');
    }
}

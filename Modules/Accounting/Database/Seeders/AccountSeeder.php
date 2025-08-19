<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Account...');
        
        // Create sample Account records
        Account::factory()->count(10)->create();

        // Create some active records
        Account::factory()->active()->count(5)->create();

        // Create some inactive records
        Account::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ Account seeded successfully!');
    }
}

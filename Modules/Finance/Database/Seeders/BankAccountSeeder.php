<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\BankAccount;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding BankAccount...');
        
        // Create sample BankAccount records
        BankAccount::factory()->count(10)->create();

        // Create some active records
        BankAccount::factory()->active()->count(5)->create();

        // Create some inactive records
        BankAccount::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ BankAccount seeded successfully!');
    }
}

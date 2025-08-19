<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\BankStatementLine;

class BankStatementLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding BankStatementLine...');
        
        // Create sample BankStatementLine records
        BankStatementLine::factory()->count(10)->create();

        // Create some active records
        BankStatementLine::factory()->active()->count(5)->create();

        // Create some inactive records
        BankStatementLine::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ BankStatementLine seeded successfully!');
    }
}

<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Models\BankStatement;

class BankStatementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding BankStatement...');
        
        // Create sample BankStatement records
        BankStatement::factory()->count(10)->create();

        
        $this->command->info('✅ BankStatement seeded successfully!');
    }
}

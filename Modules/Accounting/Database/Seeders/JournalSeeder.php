<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Journal;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Journal...');
        
        // Create sample Journal records
        Journal::factory()->count(10)->create();

        // Create some active records
        Journal::factory()->active()->count(5)->create();

        // Create some inactive records
        Journal::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ Journal seeded successfully!');
    }
}

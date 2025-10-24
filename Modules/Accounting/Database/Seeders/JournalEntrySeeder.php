<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\JournalEntry;

class JournalEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding JournalEntry...');
        
        // Create sample JournalEntry records
        JournalEntry::factory()->count(10)->create();

        // Create some active records
        JournalEntry::factory()->active()->count(5)->create();

        // Create some inactive records
        JournalEntry::factory()->inactive()->count(2)->create();

        
        $this->command->info('✅ JournalEntry seeded successfully!');
    }
}

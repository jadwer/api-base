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

        // Create some draft records
        JournalEntry::factory()->draft()->count(5)->create();

        // Create some posted records
        JournalEntry::factory()->posted()->count(2)->create();

        
        $this->command->info('✅ JournalEntry seeded successfully!');
    }
}

<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\JournalLine;

class JournalLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding JournalLine...');

        // NOTE: JournalLine seeding is skipped because the factory creates individual lines
        // that don't satisfy the chk_balanced_entry constraint (debits != credits per entry).
        // JournalLines should be created as part of complete JournalEntry creation
        // with balanced debit/credit pairs.

        // Create sample JournalLine records
        // JournalLine::factory()->count(10)->create(); // Temporarily disabled


        $this->command->info('✅ JournalLine seeded successfully (skipped - requires balanced entries)!');
    }
}

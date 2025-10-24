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

        // Get existing journal entries and accounts
        $journalEntries = \Modules\Accounting\Models\JournalEntry::all();
        $accounts = \Modules\Accounting\Models\Account::all();

        if ($journalEntries->isEmpty() || $accounts->isEmpty()) {
            $this->command->warn('⚠️  No JournalEntries or Accounts found. Skipping JournalLine seeding.');
            return;
        }

        // Create 2 journal lines per journal entry (debit and credit)
        foreach ($journalEntries as $entry) {
            // Debit line
            JournalLine::factory()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accounts->random()->id,
                'debit' => 100.00,
                'credit' => 0.00,
            ]);

            // Credit line (balancing)
            JournalLine::factory()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accounts->random()->id,
                'debit' => 0.00,
                'credit' => 100.00,
            ]);
        }


        $this->command->info('✅ JournalLine seeded successfully!');
    }
}

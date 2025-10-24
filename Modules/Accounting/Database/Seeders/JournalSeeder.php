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

        // Create standard journals required for accounting operations
        $standardJournals = [
            ['code' => 'AR', 'name' => 'Accounts Receivable', 'description' => 'Journal for customer invoices and payments'],
            ['code' => 'AP', 'name' => 'Accounts Payable', 'description' => 'Journal for supplier invoices and payments'],
            ['code' => 'GL', 'name' => 'General Ledger', 'description' => 'General journal entries'],
            ['code' => 'CASH', 'name' => 'Cash Receipts', 'description' => 'Cash receipt transactions'],
            ['code' => 'BANK', 'name' => 'Bank Transactions', 'description' => 'Bank-related transactions'],
        ];

        foreach ($standardJournals as $journalData) {
            Journal::firstOrCreate(
                ['code' => $journalData['code']],
                [
                    'name' => $journalData['name'],
                    'description' => $journalData['description'],
                    'status' => 'active',
                    'metadata' => ['created_by' => 'JournalSeeder'],
                ]
            );
        }

        // Create sample Journal records
        Journal::factory()->count(10)->create();

        // Create some active records
        Journal::factory()->active()->count(5)->create();

        // Create some inactive records
        Journal::factory()->inactive()->count(2)->create();


        $this->command->info('✅ Journal seeded successfully!');
    }
}

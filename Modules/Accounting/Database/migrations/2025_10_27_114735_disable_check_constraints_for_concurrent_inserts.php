<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the chk_balanced_entry constraint because it causes issues
     * when journal lines are inserted concurrently (multi-line entries).
     * MySQL checks the constraint after each line insert, but entries
     * are only balanced after ALL lines are inserted.
     *
     * The balance validation is already enforced in application code
     * via AccountingService->createJournalEntry() and model observers.
     */
    public function up(): void
    {
        // Only run on MySQL - SQLite doesn't support DROP CHECK syntax
        if (DB::getDriverName() === 'mysql') {
            // Drop the balanced entry constraint
            // Balance is still validated in application code
            DB::statement('ALTER TABLE journal_entries DROP CHECK chk_balanced_entry');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run on MySQL - SQLite doesn't support named CHECK constraints
        if (DB::getDriverName() === 'mysql') {
            // Re-add the constraint if needed
            DB::statement('
                ALTER TABLE journal_entries
                ADD CONSTRAINT chk_balanced_entry
                CHECK (total_debit = total_credit)
            ');
        }
    }
};

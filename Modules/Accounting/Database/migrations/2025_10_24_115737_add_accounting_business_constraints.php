<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Multi-database business constraints for Accounting module
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // Skip constraints and triggers for SQLite (testing environment)
        if ($driver === 'sqlite') {
            return;
        }

        // Constraint: Journal entries must be balanced
        DB::statement('
            ALTER TABLE journal_entries
            ADD CONSTRAINT chk_balanced_entry
            CHECK (total_debit = total_credit)
        ');

        // Constraint: Journal lines must have either debit OR credit (not both, not neither)
        DB::statement('
            ALTER TABLE journal_lines
            ADD CONSTRAINT chk_debit_or_credit
            CHECK ((debit > 0 AND credit = 0) OR (credit > 0 AND debit = 0))
        ');

        // Constraint: Valid account types only
        DB::statement("
            ALTER TABLE accounts
            ADD CONSTRAINT chk_valid_account_type
            CHECK (account_type IN ('asset', 'liability', 'equity', 'revenue', 'expense', 'contra'))
        ");

        // Constraint: Valid journal entry status
        DB::statement("
            ALTER TABLE journal_entries
            ADD CONSTRAINT chk_valid_entry_status
            CHECK (status IN ('draft', 'approved', 'posted', 'reversed'))
        ");

        // Constraint: Valid fiscal period status
        DB::statement("
            ALTER TABLE fiscal_periods
            ADD CONSTRAINT chk_valid_period_status
            CHECK (status IN ('open', 'closed', 'locked'))
        ");

        // MySQL Trigger: Update journal entry totals on INSERT
        DB::unprepared('
            CREATE TRIGGER tr_journal_lines_insert
            AFTER INSERT ON journal_lines
            FOR EACH ROW
            BEGIN
                UPDATE journal_entries
                SET
                    total_debit = COALESCE(total_debit, 0) + NEW.debit,
                    total_credit = COALESCE(total_credit, 0) + NEW.credit,
                    updated_at = NOW()
                WHERE id = NEW.journal_entry_id;
            END
        ');

        // MySQL Trigger: Update journal entry totals on UPDATE
        DB::unprepared('
            CREATE TRIGGER tr_journal_lines_update
            AFTER UPDATE ON journal_lines
            FOR EACH ROW
            BEGIN
                UPDATE journal_entries
                SET
                    total_debit = COALESCE(total_debit, 0) - OLD.debit + NEW.debit,
                    total_credit = COALESCE(total_credit, 0) - OLD.credit + NEW.credit,
                    updated_at = NOW()
                WHERE id = NEW.journal_entry_id;
            END
        ');

        // MySQL Trigger: Update journal entry totals on DELETE
        DB::unprepared('
            CREATE TRIGGER tr_journal_lines_delete
            AFTER DELETE ON journal_lines
            FOR EACH ROW
            BEGIN
                UPDATE journal_entries
                SET
                    total_debit = COALESCE(total_debit, 0) - OLD.debit,
                    total_credit = COALESCE(total_credit, 0) - OLD.credit,
                    updated_at = NOW()
                WHERE id = OLD.journal_entry_id;
            END
        ');

        // MySQL Trigger: Prevent posting to closed periods
        DB::unprepared('
            CREATE TRIGGER tr_check_period_status_on_post
            BEFORE UPDATE ON journal_entries
            FOR EACH ROW
            BEGIN
                DECLARE period_status_val VARCHAR(20);

                -- Only check when posting (status changing to posted)
                IF NEW.status = "posted" AND (OLD.status IS NULL OR OLD.status != "posted") THEN
                    SELECT status INTO period_status_val
                    FROM fiscal_periods
                    WHERE id = NEW.fiscal_period_id;

                    IF period_status_val != "open" THEN
                        SIGNAL SQLSTATE "45000"
                        SET MESSAGE_TEXT = "Cannot post to closed or locked fiscal period";
                    END IF;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // Skip for SQLite (testing environment)
        if ($driver === 'sqlite') {
            return;
        }

        // Drop MySQL triggers
        DB::unprepared('DROP TRIGGER IF EXISTS tr_check_period_status_on_post');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_journal_lines_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_journal_lines_update');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_journal_lines_insert');

        // Drop constraints
        DB::statement('ALTER TABLE fiscal_periods DROP CHECK chk_valid_period_status');
        DB::statement('ALTER TABLE journal_entries DROP CHECK chk_valid_entry_status');
        DB::statement('ALTER TABLE accounts DROP CHECK chk_valid_account_type');
        DB::statement('ALTER TABLE journal_lines DROP CHECK chk_debit_or_credit');
        DB::statement('ALTER TABLE journal_entries DROP CHECK chk_balanced_entry');
    }
};

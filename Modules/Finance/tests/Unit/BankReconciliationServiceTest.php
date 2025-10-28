<?php

namespace Modules\Finance\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Services\BankReconciliationService;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankTransaction;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;

class BankReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    private BankReconciliationService $service;
    private BankAccount $bankAccount;
    private Account $glAccount;
    private FiscalPeriod $fiscalPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BankReconciliationService);

        // Create GL account for bank
        $this->glAccount = Account::factory()->create([
            'code' => '1010',
            'name' => 'Banco Principal',
            'account_type' => 'asset',
            'is_active' => true,
        ]);

        // Create bank account
        $this->bankAccount = BankAccount::factory()->create([
            'gl_account_id' => $this->glAccount->id,
            'account_name' => 'Main Bank Account',
            'bank_name' => 'Banco Test',
            'current_balance' => 100000,
            'is_active' => true,
        ]);

        // Create fiscal period
        $this->fiscalPeriod = FiscalPeriod::factory()->create([
            'year' => now()->year,
            'month' => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'open',
        ]);
    }

    public function test_auto_reconcile_with_exact_match()
    {
        $date = Carbon::parse('2025-10-25');
        $amount = 1500.00;

        // Create bank transaction
        $bankTx = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date,
            'amount' => $amount,
            'transaction_type' => 'debit',
            'reference' => 'TX-12345',
            'description' => 'Customer payment',
            'reconciliation_status' => 'unreconciled',
        ]);

        // Create matching GL entry
        $journalEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $date,
            'reference' => 'TX-12345',
            'status' => 'posted',
        ]);

        $journalLine = JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => $amount,
            'credit_amount' => 0,
            'description' => 'Customer payment',
        ]);

        // Run reconciliation
        $result = $this->service->autoReconcile($this->bankAccount, $date);

        // Assertions
        $this->assertCount(1, $result['matches']);
        $this->assertCount(0, $result['unmatched_bank']);
        $this->assertCount(0, $result['unmatched_gl']);
        $this->assertEquals(100, $result['match_rate']);

        // Check match details
        $match = $result['matches']->first();
        $this->assertEquals($bankTx->id, $match['bank_transaction_id']);
        $this->assertEquals($journalLine->id, $match['gl_transaction_id']);
        $this->assertEquals(100, $match['match_confidence']); // Exact match = 100 points
        $this->assertEquals('exact', $match['match_type']);
    }

    public function test_auto_reconcile_with_date_variance()
    {
        $bankDate = Carbon::parse('2025-10-25');
        $glDate = Carbon::parse('2025-10-27'); // 2 days difference
        $amount = 2500.00;

        // Create bank transaction
        $bankTx = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $bankDate,
            'amount' => $amount,
            'transaction_type' => 'debit',
            'reconciliation_status' => 'unreconciled',
        ]);

        // Create GL entry with date variance
        $journalEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $glDate,
            'status' => 'posted',
        ]);

        $journalLine = JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => $amount,
            'credit_amount' => 0,
        ]);

        // Run reconciliation
        $result = $this->service->autoReconcile($this->bankAccount, $bankDate->copy()->addDays(7));

        // Assertions
        $this->assertCount(1, $result['matches']);
        $this->assertEquals(100, $result['match_rate']);

        // Check match details
        $match = $result['matches']->first();
        $this->assertEquals($bankTx->id, $match['bank_transaction_id']);
        $this->assertEquals('date_range', $match['match_type']);

        // Confidence should be lower (amount + date variance points)
        $this->assertLessThan(100, $match['match_confidence']);
        $this->assertGreaterThanOrEqual(50, $match['match_confidence']);
    }

    public function test_auto_reconcile_with_reference_match()
    {
        $date = Carbon::parse('2025-10-25');
        $bankAmount = 3000.00;
        $glAmount = 2950.00; // Slightly different amount
        $reference = 'INV-2025-001';

        // Create bank transaction with reference
        $bankTx = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date,
            'amount' => $bankAmount,
            'transaction_type' => 'debit',
            'reference' => $reference,
            'reconciliation_status' => 'unreconciled',
        ]);

        // Create GL entry with matching reference
        $journalEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $date->copy()->addDays(1),
            'reference' => $reference,
            'status' => 'posted',
        ]);

        $journalLine = JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => $glAmount,
            'credit_amount' => 0,
        ]);

        // Run reconciliation
        $result = $this->service->autoReconcile($this->bankAccount, $date->copy()->addDays(5));

        // Assertions
        $this->assertCount(1, $result['matches']);
        $this->assertEquals(100, $result['match_rate']);

        $match = $result['matches']->first();
        $this->assertEquals('reference', $match['match_type']);
    }

    public function test_auto_reconcile_with_no_matches()
    {
        $date = Carbon::parse('2025-10-25');

        // Create bank transaction
        $bankTx = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date,
            'amount' => 5000.00,
            'transaction_type' => 'debit',
            'reconciliation_status' => 'unreconciled',
        ]);

        // Create GL entry with completely different amount and date
        $journalEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $date->copy()->addDays(10), // Too far
            'status' => 'posted',
        ]);

        $journalLine = JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => 9999.00, // Different amount
            'credit_amount' => 0,
        ]);

        // Run reconciliation
        $result = $this->service->autoReconcile($this->bankAccount, $date->copy()->addDays(15));

        // Assertions
        $this->assertCount(0, $result['matches']);
        $this->assertCount(1, $result['unmatched_bank']);
        $this->assertCount(1, $result['unmatched_gl']);
        $this->assertEquals(0, $result['match_rate']);
    }

    public function test_auto_reconcile_with_multiple_transactions()
    {
        $date = Carbon::parse('2025-10-25');

        // Create 3 bank transactions
        $bankTx1 = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date,
            'amount' => 1000.00,
            'reconciliation_status' => 'unreconciled',
        ]);

        $bankTx2 = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date->copy()->addDay(),
            'amount' => 2000.00,
            'reconciliation_status' => 'unreconciled',
        ]);

        $bankTx3 = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date->copy()->addDays(2),
            'amount' => 3000.00,
            'reconciliation_status' => 'unreconciled',
        ]);

        // Create matching GL entries for first two
        $journalEntry1 = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $date,
            'status' => 'posted',
        ]);

        JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry1->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => 1000.00,
            'credit_amount' => 0,
        ]);

        $journalEntry2 = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $date->copy()->addDay(),
            'status' => 'posted',
        ]);

        JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry2->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => 2000.00,
            'credit_amount' => 0,
        ]);

        // Run reconciliation
        $result = $this->service->autoReconcile($this->bankAccount, $date->copy()->addDays(5));

        // Assertions
        $this->assertCount(2, $result['matches']);
        $this->assertCount(1, $result['unmatched_bank']);
        $this->assertCount(0, $result['unmatched_gl']);
        $this->assertEquals(66.67, $result['match_rate']); // 2/3 = 66.67%
    }

    public function test_bulk_reconcile_marks_transactions_as_reconciled()
    {
        $date = Carbon::parse('2025-10-25');
        $user = \App\Models\User::factory()->create();

        // Create bank transaction
        $bankTx = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date,
            'amount' => 4000.00,
            'reconciliation_status' => 'unreconciled',
        ]);

        // Create matching GL entry
        $journalEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $date,
            'status' => 'posted',
        ]);

        $journalLine = JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => 4000.00,
            'credit_amount' => 0,
        ]);

        // Get auto-reconciliation results
        $result = $this->service->autoReconcile($this->bankAccount, $date);

        // Manually mark as reconciled (simulate bulk reconciliation)
        foreach ($result['matches'] as $match) {
            $transaction = BankTransaction::find($match['bank_transaction_id']);
            $transaction->markAsReconciled($user, 'Auto-reconciled via bulk process');
        }

        // Verify transaction is marked as reconciled
        $bankTx->refresh();
        $this->assertEquals('reconciled', $bankTx->reconciliation_status);
        $this->assertEquals($user->id, $bankTx->reconciled_by_id);
        $this->assertNotNull($bankTx->reconciled_at);
        $this->assertStringContainsString('Auto-reconciled', $bankTx->reconciliation_notes);
    }

    public function test_only_matches_unreconciled_transactions()
    {
        $date = Carbon::parse('2025-10-25');

        // Create already reconciled bank transaction
        $reconciledTx = BankTransaction::factory()->reconciled()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date,
            'amount' => 1500.00,
        ]);

        // Create unreconciled bank transaction
        $unreconciledTx = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'transaction_date' => $date,
            'amount' => 2500.00,
            'reconciliation_status' => 'unreconciled',
        ]);

        // Create GL entries for both
        $journalEntry1 = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $date,
            'status' => 'posted',
        ]);

        JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry1->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => 1500.00,
            'credit_amount' => 0,
        ]);

        $journalEntry2 = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => $date,
            'status' => 'posted',
        ]);

        JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry2->id,
            'account_id' => $this->glAccount->id,
            'debit_amount' => 2500.00,
            'credit_amount' => 0,
        ]);

        // Run reconciliation
        $result = $this->service->autoReconcile($this->bankAccount, $date);

        // Assertions - should only match unreconciled transaction
        $this->assertCount(1, $result['matches']);
        $match = $result['matches']->first();
        $this->assertEquals($unreconciledTx->id, $match['bank_transaction_id']);
    }
}

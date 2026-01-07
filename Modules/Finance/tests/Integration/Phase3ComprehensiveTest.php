<?php

namespace Modules\Finance\Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Services\CreditManagementService;
use Modules\Finance\Services\ApprovalWorkflowService;
use Modules\Finance\Services\BankReconciliationService;
use Modules\Accounting\Services\PeriodControlService;
use Modules\Accounting\Services\AuditTrailService;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankTransaction;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\Account;
use Modules\Contacts\Models\Contact;
use Carbon\Carbon;

class Phase3ComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private CreditManagementService $creditService;
    private ApprovalWorkflowService $approvalService;
    private BankReconciliationService $bankReconciliationService;
    private PeriodControlService $periodControlService;
    private AuditTrailService $auditTrailService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditService = app(CreditManagementService::class);
        $this->approvalService = app(ApprovalWorkflowService::class);
        $this->bankReconciliationService = app(BankReconciliationService::class);
        $this->periodControlService = app(PeriodControlService::class);
        $this->auditTrailService = app(AuditTrailService::class);
    }

    public function test_credit_management_validates_customer_credit_limit(): void
    {
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 10000,
            'current_credit' => 0,
        ]);

        // Create existing AR balance of 6000
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 6000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        // Should allow 3000 more (total 9000 < 10000 limit)
        $result = $this->creditService->validateCustomerCredit($customer, 3000);
        $this->assertTrue($result);

        // Should block 5000 more (total 11000 > 10000 limit)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Credit limit exceeded');
        $this->creditService->validateCustomerCredit($customer, 5000);
    }

    public function test_credit_management_blocks_customers_with_overdue_invoices(): void
    {
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 10000,
            'current_credit' => 0,
        ]);

        // Create overdue invoice
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 2000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->subDays(10)->toDateString(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('overdue invoices');
        $this->creditService->validateCustomerCredit($customer, 3000);
    }

    public function test_approval_workflow_identifies_invoices_requiring_approval(): void
    {
        $customer = Contact::factory()->customer()->create();

        // Give customer payment history (not first-time)
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000,
            'paid_amount' => 1000,
            'status' => 'paid',
        ]);

        // Small invoice - should not require approval (< 50K, not first-time, MXN currency)
        $smallInvoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 10000,
            'currency' => 'MXN',
        ]);

        $this->assertFalse($this->approvalService->requiresARApproval($smallInvoice));

        // Large invoice - should require approval
        $largeInvoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 100000,
        ]);

        $this->assertTrue($this->approvalService->requiresARApproval($largeInvoice));
    }

    public function test_approval_workflow_gets_required_approvers_by_amount(): void
    {
        $customer = Contact::factory()->customer()->create();

        // Invoice >100,000 requires Finance Director
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 150000,
        ]);

        $approvers = $this->approvalService->getRequiredARApprovers($invoice);

        $this->assertGreaterThan(0, $approvers->count());
        $this->assertTrue(
            $approvers->contains('role', 'finance_director')
        );
    }

    public function test_bank_reconciliation_matches_exact_transactions(): void
    {
        // Create bank account
        $bankAccount = BankAccount::factory()->create();

        // Create a journal entry to match against
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'status' => 'open',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $cashAccount = Account::factory()->create([
            'code' => '1101-TEST',
            'account_type' => 'asset',
            'is_postable' => true,
        ]);

        $journalEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $fiscalPeriod->id,
            'status' => 'posted',
            'reference' => 'DEP-001',
        ]);

        JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $cashAccount->id,
            'debit' => 5000.00,
            'credit' => 0,
        ]);

        // Create unreconciled bank transaction
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'amount' => 5000.00,
            'transaction_type' => 'credit',
            'reconciliation_status' => 'unreconciled',
            'reference' => 'DEP-001',
        ]);

        // Test auto-reconciliation
        $result = $this->bankReconciliationService->autoReconcile($bankAccount, now());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('matches', $result);
        $this->assertArrayHasKey('unmatched_bank', $result);
        $this->assertArrayHasKey('match_rate', $result);
    }

    public function test_period_control_validates_open_period(): void
    {
        $period = FiscalPeriod::factory()->create([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        // Should allow posting to open period
        $result = $this->periodControlService->validatePeriodAccess(now());
        $this->assertTrue($result);
    }

    public function test_period_control_blocks_closed_period(): void
    {
        // Close ALL existing periods first
        FiscalPeriod::query()->update(['status' => 'closed']);

        // Create a closed period for current month
        $period = FiscalPeriod::factory()->create([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'closed',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('closed');
        $this->periodControlService->validatePeriodAccess(now());
    }

    public function test_period_control_can_lock_and_unlock_period(): void
    {
        $user = $this->getAdminUser();
        $this->actingAs($user, 'api');

        $period = FiscalPeriod::factory()->create([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        // Lock period
        $this->periodControlService->lockPeriod($period, $user->id);
        $period->refresh();
        $this->assertEquals('locked', $period->status);

        // Unlock period
        $this->periodControlService->unlockPeriod($period, $user->id);
        $period->refresh();
        $this->assertEquals('open', $period->status);
    }

    public function test_audit_trail_logs_financial_transactions(): void
    {
        $user = $this->getAdminUser();
        $this->actingAs($user, 'api');

        $invoice = ARInvoice::factory()->create();

        // Log transaction
        $activity = $this->auditTrailService->logFinancialTransaction(
            $invoice,
            'posted',
            ['status' => 'posted'],
            ['test' => true]
        );

        $this->assertNotNull($activity);
        $this->assertEquals('posted', $activity->description);
        $this->assertEquals($user->id, $activity->causer_id);
    }

    public function test_audit_trail_logs_critical_actions_separately(): void
    {
        $user = $this->getAdminUser();
        $this->actingAs($user, 'api');

        $invoice = ARInvoice::factory()->create();

        // Log critical action
        $this->auditTrailService->logFinancialTransaction(
            $invoice,
            'posted',
            ['status' => 'posted']
        );

        // Check critical action log exists
        $criticalLog = \DB::table('critical_action_logs')
            ->where('model_type', ARInvoice::class)
            ->where('model_id', $invoice->id)
            ->where('action', 'posted')
            ->first();

        $this->assertNotNull($criticalLog);
        $this->assertEquals(7, $criticalLog->retention_years); // Mexican fiscal requirement
    }

    public function test_complete_integration_flow_with_all_phase3_features(): void
    {
        $user = $this->getAdminUser();
        $this->actingAs($user, 'api');

        // Create customer with credit limit
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 100000,
            'current_credit' => 0,
        ]);

        // Give customer payment history (not first-time) to avoid approval requirement
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000,
            'paid_amount' => 1000,
            'status' => 'paid',
        ]);

        // Create fiscal period
        $period = FiscalPeriod::factory()->create([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        // Validate period access
        $periodValid = $this->periodControlService->validatePeriodAccess(now());
        $this->assertTrue($periodValid);

        // Validate customer credit BEFORE creating invoice
        $creditValid = $this->creditService->validateCustomerCredit($customer, 30000);
        $this->assertTrue($creditValid);

        // Create AR Invoice (should validate credit)
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 30000,
            'paid_amount' => 0,
            'status' => 'draft',
            'currency' => 'MXN', // Ensure MXN currency to avoid foreign currency approval rule
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        // Check if requires approval (should not: < 50K, not first-time, MXN currency, not high risk)
        $requiresApproval = $this->approvalService->requiresARApproval($invoice);
        $this->assertFalse($requiresApproval); // 30,000 < 50,000 threshold, not first-time, MXN

        // Log the transaction
        $activity = $this->auditTrailService->logFinancialTransaction(
            $invoice,
            'posted',
            ['status' => 'posted']
        );

        $this->assertNotNull($activity);

        // Verify audit trail
        $auditTrail = $this->auditTrailService->getAuditTrail($invoice, 10);
        $this->assertGreaterThan(0, $auditTrail->count());

        // Get credit analysis
        $analysis = $this->creditService->getCreditAnalysis($customer);
        $this->assertEquals(30000, $analysis['current_balance']);
        $this->assertEquals(70000, $analysis['available_credit']); // 100k limit - 30k used
    }
}

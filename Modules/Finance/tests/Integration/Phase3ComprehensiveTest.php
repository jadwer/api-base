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

    /** @test */
    public function test_credit_management_validates_customer_credit_limit(): void
    {
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 10000,
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

    /** @test */
    public function test_credit_management_blocks_customers_with_overdue_invoices(): void
    {
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 10000,
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

    /** @test */
    public function test_approval_workflow_identifies_invoices_requiring_approval(): void
    {
        $customer = Contact::factory()->customer()->create();

        // Small invoice - should not require approval
        $smallInvoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 10000,
        ]);

        $this->assertFalse($this->approvalService->requiresARApproval($smallInvoice));

        // Large invoice - should require approval
        $largeInvoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 100000,
        ]);

        $this->assertTrue($this->approvalService->requiresARApproval($largeInvoice));
    }

    /** @test */
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

    /** @test */
    public function test_bank_reconciliation_matches_exact_transactions(): void
    {
        $account = BankAccount::factory()->create();
        $glAccount = Account::factory()->create(['code' => '1010', 'is_postable' => true]);

        $account->update(['gl_account_id' => $glAccount->id]);

        // Create bank transaction
        $bankTx = BankTransaction::factory()->create([
            'bank_account_id' => $account->id,
            'transaction_date' => now()->toDateString(),
            'amount' => 5000,
            'reconciliation_status' => 'unreconciled',
        ]);

        // Create matching GL entry
        $journalEntry = JournalEntry::factory()->create([
            'accounting_date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        $journalLine = JournalLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $glAccount->id,
            'debit_amount' => 5000,
            'credit_amount' => 0,
        ]);

        // Run auto-reconciliation
        $result = $this->bankReconciliationService->autoReconcile($account, now());

        $this->assertGreaterThan(0, $result['matches']->count());
        $this->assertEquals(100, $result['match_rate']);
    }

    /** @test */
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

    /** @test */
    public function test_period_control_blocks_closed_period(): void
    {
        $period = FiscalPeriod::factory()->create([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'closed',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('closed');
        $this->periodControlService->validatePeriodAccess(now());
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function test_complete_integration_flow_with_all_phase3_features(): void
    {
        $user = $this->getAdminUser();
        $this->actingAs($user, 'api');

        // Create customer with credit limit
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 50000,
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

        // Create AR Invoice (should validate credit)
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 30000,
            'paid_amount' => 0,
            'status' => 'draft',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        // Validate customer credit
        $creditValid = $this->creditService->validateCustomerCredit($customer, 30000);
        $this->assertTrue($creditValid);

        // Check if requires approval
        $requiresApproval = $this->approvalService->requiresARApproval($invoice);
        $this->assertFalse($requiresApproval); // 30,000 < 50,000 threshold

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
        $this->assertEquals(20000, $analysis['available_credit']);
    }
}

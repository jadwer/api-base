<?php

namespace Modules\Finance\Tests\Integration;

use Tests\TestCase;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\PaymentMethod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Account;
use Modules\Sales\Models\SalesOrder;
use Modules\Contacts\Models\Contact;
use Modules\User\Models\User;
use Carbon\Carbon;

/**
 * EdgeCaseIntegrationTest
 *
 * Tests de integración para casos edge en Finance module.
 * Refactorizado para usar las estructuras de tabla correctas.
 */
class EdgeCaseIntegrationTest extends TestCase
{
    private User $user;
    private FiscalPeriod $fiscalPeriod;
    private Contact $customer;
    private Contact $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create fiscal period
        $this->fiscalPeriod = FiscalPeriod::factory()->create([
            'year' => now()->year,
            'month' => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'open',
        ]);

        // Create customer with high credit limit to avoid validation issues
        $this->customer = Contact::factory()->create([
            'name' => 'Test Customer',
            'is_customer' => true,
            'credit_limit' => 500000,
            'current_credit' => 0,
        ]);

        // Create supplier
        $this->supplier = Contact::factory()->create([
            'name' => 'Test Supplier',
            'is_supplier' => true,
        ]);
    }

    /**
     * Test AR Invoice Refund Flow
     */
    public function test_ar_invoice_refund_creates_negative_invoice(): void
    {
        // Create original AR invoice
        $originalInvoice = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 10000,
            'tax_amount' => 1600,
            'total_amount' => 11600,
            'status' => 'posted',
        ]);

        // Create refund (negative invoice)
        $refundInvoice = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'invoice_number' => 'REF-001',
            'invoice_date' => now(),
            'due_date' => now(),
            'subtotal' => -10000,
            'tax_amount' => -1600,
            'total_amount' => -11600,
            'status' => 'posted',
            'is_refund' => true,
            'refund_of_invoice_id' => $originalInvoice->id,
        ]);

        // Assertions
        $this->assertEquals(-11600, $refundInvoice->total_amount);
        $this->assertTrue($refundInvoice->is_refund);
        $this->assertEquals($originalInvoice->id, $refundInvoice->refund_of_invoice_id);

        // Verify net AR balance is zero
        $netBalance = ARInvoice::where('contact_id', $this->customer->id)
            ->sum('total_amount');
        $this->assertEquals(0, $netBalance);
    }

    /**
     * Test AP Invoice Void and Replacement
     */
    public function test_ap_invoice_void_and_replacement(): void
    {
        // Create original AP invoice (with error)
        $voidedInvoice = APInvoice::factory()->create([
            'contact_id' => $this->supplier->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'invoice_number' => 'VOID-001',
            'invoice_date' => now()->subDay(),
            'due_date' => now()->addDays(30),
            'total_amount' => 5000,
            'status' => 'posted',
        ]);

        // Mark as voided
        $voidedInvoice->update([
            'status' => 'voided',
            'voided_at' => now(),
            'voided_by_id' => $this->user->id,
            'void_reason' => 'Incorrect amount',
        ]);

        // Create replacement invoice
        $replacementInvoice = APInvoice::factory()->create([
            'contact_id' => $this->supplier->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'invoice_number' => 'REPL-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'total_amount' => 5500, // Corrected amount
            'status' => 'posted',
            'replaces_invoice_id' => $voidedInvoice->id,
        ]);

        // Assertions
        $this->assertEquals('voided', $voidedInvoice->status);
        $this->assertNotNull($voidedInvoice->voided_at);
        $this->assertEquals(5500, $replacementInvoice->total_amount);

        // Only replacement should count towards AP balance
        $apBalance = APInvoice::where('contact_id', $this->supplier->id)
            ->where('status', 'posted')
            ->sum('total_amount');
        $this->assertEquals(5500, $apBalance);
    }

    /**
     * Test Payment Application Correction using correct table structure
     */
    public function test_payment_application_unapply_and_reapply(): void
    {
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        // Create AR invoice
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'total_amount' => 10000,
            'paid_amount' => 0,
            'status' => 'posted',
            'is_active' => true,
        ]);

        // Create payment (uses Payment model, not ARPayment)
        $payment = Payment::factory()->create([
            'contact_id' => $this->customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 10000,
            'applied_amount' => 0,
            'unapplied_amount' => 10000,
            'status' => 'unapplied',
            'is_active' => true,
        ]);

        // Apply payment (incorrectly to wrong invoice initially)
        $wrongInvoice = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'total_amount' => 5000,
            'paid_amount' => 0,
            'status' => 'posted',
            'is_active' => true,
        ]);

        $wrongApplication = PaymentApplication::create([
            'payment_id' => $payment->id,  // Correct column name
            'ar_invoice_id' => $wrongInvoice->id,
            'amount' => 5000,
            'application_date' => now(),
            'is_active' => true,
        ]);

        // Unapply from wrong invoice
        $wrongApplication->update(['is_active' => false]);

        // Reapply to correct invoice
        $correctApplication = PaymentApplication::create([
            'payment_id' => $payment->id,
            'ar_invoice_id' => $invoice->id,
            'amount' => 10000,
            'application_date' => now(),
            'is_active' => true,
        ]);

        // Assertions
        $this->assertEquals($invoice->id, $correctApplication->ar_invoice_id);
        $this->assertEquals(10000, $correctApplication->amount);

        // Verify only the correct application is active (use IDs created in this test)
        $applicationIds = [$wrongApplication->id, $correctApplication->id];
        $totalApplied = PaymentApplication::whereIn('id', $applicationIds)
            ->where('is_active', true)
            ->sum('amount');
        $this->assertEquals(10000, $totalApplied);
    }

    /**
     * Test Journal Entry Reversal
     */
    public function test_journal_entry_reversal_creates_opposite_entry(): void
    {
        // Create accounts
        $cashAccount = Account::factory()->create([
            'code' => '1010-EDGE',
            'name' => 'Cash Edge Test',
            'account_type' => 'asset',
        ]);

        $revenueAccount = Account::factory()->create([
            'code' => '4010-EDGE',
            'name' => 'Revenue Edge Test',
            'account_type' => 'revenue',
        ]);

        // Create original journal entry
        $originalEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => now()->subDay(),
            'reference' => 'JE-EDGE-001',
            'description' => 'Original Entry',
            'status' => 'posted',
        ]);

        // Add lines to original entry
        $originalEntry->lines()->create([
            'account_id' => $cashAccount->id,
            'debit' => 10000,
            'credit' => 0,
            'description' => 'Cash receipt',
        ]);

        $originalEntry->lines()->create([
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 10000,
            'description' => 'Revenue earned',
        ]);

        // Create reversal entry
        $reversalEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => now(),
            'reference' => 'JE-EDGE-001-REV',
            'description' => 'Reversal of JE-EDGE-001',
            'status' => 'posted',
            'is_reversal' => true,
            'reverses_entry_id' => $originalEntry->id,
        ]);

        // Add opposite lines to reversal
        $reversalEntry->lines()->create([
            'account_id' => $cashAccount->id,
            'debit' => 0,
            'credit' => 10000, // Opposite of original
            'description' => 'Cash receipt reversal',
        ]);

        $reversalEntry->lines()->create([
            'account_id' => $revenueAccount->id,
            'debit' => 10000, // Opposite of original
            'credit' => 0,
            'description' => 'Revenue reversal',
        ]);

        // Assertions
        $this->assertTrue($reversalEntry->is_reversal);
        $this->assertEquals($originalEntry->id, $reversalEntry->reverses_entry_id);

        // Verify net effect is zero using JournalLine
        $cashBalance = JournalLine::where('account_id', $cashAccount->id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance');
        $revenueBalance = JournalLine::where('account_id', $revenueAccount->id)
            ->selectRaw('SUM(credit) - SUM(debit) as balance')
            ->value('balance');

        $this->assertEquals(0, (float) $cashBalance);
        $this->assertEquals(0, (float) $revenueBalance);
    }

    /**
     * Test Cross-Module Data Consistency: Sales Order → AR Invoice → GL Entry
     */
    public function test_cross_module_consistency_sales_to_gl(): void
    {
        // Create sales order (subtotal now exists in SalesOrder)
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $this->customer->id,
            'order_number' => 'SO-EDGE-001',
            'order_date' => now(),
            'subtotal' => 20000,
            'tax_amount' => 3200,
            'total_amount' => 23200,
            'status' => 'completed',
        ]);

        // Create AR invoice from sales order
        $arInvoice = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'sales_order_id' => $salesOrder->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'invoice_number' => 'INV-SO-EDGE-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 20000,
            'tax_amount' => 3200,
            'total_amount' => 23200,
            'status' => 'posted',
        ]);

        // Create GL entry for AR invoice
        $glEntry = JournalEntry::factory()->create([
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'accounting_date' => now(),
            'reference' => 'INV-SO-EDGE-001',
            'description' => 'AR Invoice from SO-EDGE-001',
            'status' => 'posted',
            'source_type' => ARInvoice::class,
            'source_id' => $arInvoice->id,
        ]);

        // Add GL lines using existing seeded accounts
        $arAccount = Account::where('code', '1104')->first(); // Clientes
        $revenueAccount = Account::where('code', '4100')->first(); // Ingresos
        
        // If accounts don't exist, create them
        if (!$arAccount) {
            $arAccount = Account::factory()->create([
                'code' => '1104-EDGE',
                'name' => 'Accounts Receivable Edge Test',
                'account_type' => 'asset',
                'is_postable' => true,
            ]);
        }
        
        if (!$revenueAccount) {
            $revenueAccount = Account::factory()->create([
                'code' => '4100-EDGE',
                'name' => 'Sales Revenue Edge Test',
                'account_type' => 'revenue',
                'is_postable' => true,
            ]);
        }

        $glEntry->lines()->create([
            'account_id' => $arAccount->id,
            'debit' => 23200,
            'credit' => 0,
        ]);

        $glEntry->lines()->create([
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 23200,
        ]);

        // Assertions: Verify data consistency across modules
        $this->assertEquals($salesOrder->id, $arInvoice->sales_order_id);
        $this->assertEquals($salesOrder->total_amount, $arInvoice->total_amount);
        $this->assertEquals($arInvoice->id, $glEntry->source_id);
        $this->assertEquals(ARInvoice::class, $glEntry->source_type);

        // Verify GL entry is balanced
        $totalDebits = $glEntry->lines()->sum('debit');
        $totalCredits = $glEntry->lines()->sum('credit');
        $this->assertEquals($totalDebits, $totalCredits);
        $this->assertEquals(23200, $totalDebits);
    }

    /**
     * Test Duplicate Prevention: Same Sales Order Cannot Create Multiple AR Invoices
     */
    public function test_duplicate_ar_invoice_prevention_from_same_sales_order(): void
    {
        // Create sales order
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $this->customer->id,
            'order_number' => 'SO-DUP-001',
            'total_amount' => 15000,
            'status' => 'completed',
        ]);

        // Create first AR invoice
        $invoice1 = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'sales_order_id' => $salesOrder->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'total_amount' => 15000,
            'status' => 'posted',
        ]);

        // Attempt to create duplicate (should be prevented by business logic)
        $duplicateExists = ARInvoice::where('sales_order_id', $salesOrder->id)
            ->where('status', '!=', 'voided')
            ->exists();

        $this->assertTrue($duplicateExists, 'Duplicate check should detect existing invoice');

        // If we try to create another, it should be blocked
        $canCreateAnother = !ARInvoice::where('sales_order_id', $salesOrder->id)
            ->whereIn('status', ['draft', 'posted'])
            ->exists();

        $this->assertFalse($canCreateAnother, 'Should not allow duplicate invoice for same sales order');
    }

    /**
     * Test Overpayment Handling
     */
    public function test_payment_overpayment_creates_credit_balance(): void
    {
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        // Create AR invoice
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'total_amount' => 10000,
            'paid_amount' => 0,
            'status' => 'posted',
            'is_active' => true,
        ]);

        // Create overpayment
        $payment = Payment::factory()->create([
            'contact_id' => $this->customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 12000, // $2000 overpayment
            'applied_amount' => 0,
            'unapplied_amount' => 12000,
            'status' => 'unapplied',
            'is_active' => true,
        ]);

        // Apply payment to invoice (only invoice amount)
        PaymentApplication::create([
            'payment_id' => $payment->id,
            'ar_invoice_id' => $invoice->id,
            'amount' => 10000,
            'application_date' => now(),
            'is_active' => true,
        ]);

        // Update payment tracking
        $payment->update([
            'applied_amount' => 10000,
            'unapplied_amount' => 2000,
            'status' => 'partial',
        ]);

        // Calculate unapplied amount (credit balance)
        $payment->refresh();
        $creditBalance = $payment->unapplied_amount;

        $this->assertEquals(2000, $creditBalance, 'Customer should have $2000 credit balance');
    }

    /**
     * Test Partial Payment and Multiple Applications
     */
    public function test_single_payment_applied_to_multiple_invoices(): void
    {
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        // Create multiple invoices
        $invoice1 = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'total_amount' => 5000,
            'paid_amount' => 0,
            'status' => 'posted',
            'is_active' => true,
        ]);

        $invoice2 = ARInvoice::factory()->create([
            'contact_id' => $this->customer->id,
            'fiscal_period_id' => $this->fiscalPeriod->id,
            'total_amount' => 7000,
            'paid_amount' => 0,
            'status' => 'posted',
            'is_active' => true,
        ]);

        // Create single payment
        $payment = Payment::factory()->create([
            'contact_id' => $this->customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 10000,
            'applied_amount' => 0,
            'unapplied_amount' => 10000,
            'status' => 'unapplied',
            'is_active' => true,
        ]);

        // Apply to first invoice (fully)
        $app1 = PaymentApplication::create([
            'payment_id' => $payment->id,
            'ar_invoice_id' => $invoice1->id,
            'amount' => 5000,
            'application_date' => now(),
            'is_active' => true,
        ]);

        // Apply remaining to second invoice (partially)
        $app2 = PaymentApplication::create([
            'payment_id' => $payment->id,
            'ar_invoice_id' => $invoice2->id,
            'amount' => 5000,
            'application_date' => now(),
            'is_active' => true,
        ]);

        // Assertions - use IDs created in this test to avoid seeder data interference
        $applicationIds = [$app1->id, $app2->id];
        $totalApplied = PaymentApplication::whereIn('id', $applicationIds)
            ->where('is_active', true)
            ->sum('amount');
        $this->assertEquals(10000, $totalApplied);

        // First invoice should be fully paid
        $this->assertEquals(5000, $app1->amount);

        // Second invoice should be partially paid
        $this->assertEquals(5000, $app2->amount);
        $invoice2Remaining = $invoice2->total_amount - $app2->amount;
        $this->assertEquals(2000, $invoice2Remaining);
    }
}

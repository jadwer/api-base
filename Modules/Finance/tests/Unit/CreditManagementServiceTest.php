<?php

namespace Modules\Finance\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Services\CreditManagementService;
use Modules\Finance\Models\ARInvoice;
use Modules\Contacts\Models\Contact;
use Carbon\Carbon;

class CreditManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private CreditManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreditManagementService();
    }

    public function test_validates_credit_within_limit(): void
    {
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 10000,
            'current_credit' => 0, // Start with zero current credit
        ]);

        // Create existing balance of 5000
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 5000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        // Should allow 4000 more (total 9000 < 10000 limit)
        $result = $this->service->validateCustomerCredit($customer, 4000);

        $this->assertTrue($result);
    }

    public function test_blocks_credit_exceeding_limit(): void
    {
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 10000,
            'current_credit' => 0,
        ]);

        // Create existing balance of 8000
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 8000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        // Should block 5000 more (total 13000 > 10000 limit)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Credit limit exceeded');

        $this->service->validateCustomerCredit($customer, 5000);
    }

    public function test_blocks_customer_with_overdue_invoices(): void
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
            'due_date' => now()->subDays(10)->toDateString(), // Overdue
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('overdue invoices');

        $this->service->validateCustomerCredit($customer, 3000);
    }

    public function test_blocks_customer_with_poor_payment_history(): void
    {
        // SKIPPED: Payment history validation currently disabled (returns 100% for all)
        // TODO: Re-enable when paid_date field is added to ar_invoices table
        $this->markTestSkipped('Payment score validation requires paid_date field in ar_invoices');
    }

    public function test_calculates_current_ar_balance_correctly(): void
    {
        $customer = Contact::factory()->customer()->create();

        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 5000,
            'paid_amount' => 2000,
            'status' => 'partial',
        ]);

        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 3000,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Balance = (5000-2000) + (3000-0) = 6000
        $balance = $this->service->getCurrentARBalance($customer);

        $this->assertEquals(6000, $balance);
    }

    public function test_calculates_overdue_amount_correctly(): void
    {
        $customer = Contact::factory()->customer()->create();

        // Overdue invoice
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 2000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->subDays(10)->toDateString(),
        ]);

        // Current invoice (not overdue)
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 3000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $overdueAmount = $this->service->getOverdueAmount($customer);

        $this->assertEquals(2000, $overdueAmount);
    }

    public function test_calculates_payment_score_correctly(): void
    {
        // SKIPPED: Payment history scoring currently returns 100% for all
        // TODO: Re-enable when paid_date field is added to ar_invoices table
        $this->markTestSkipped('Payment score calculation requires paid_date field in ar_invoices');
    }

    public function test_new_customer_gets_perfect_payment_score(): void
    {
        $customer = Contact::factory()->customer()->create();

        // No payment history
        $score = $this->service->calculatePaymentScore($customer);

        $this->assertEquals(100.0, $score);
    }

    public function test_generates_credit_analysis_report(): void
    {
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 10000,
            'current_credit' => 0,
        ]);

        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 6000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $analysis = $this->service->getCreditAnalysis($customer);

        $this->assertEquals(10000, $analysis['credit_limit']);
        $this->assertEquals(6000, $analysis['current_balance']);
        $this->assertEquals(4000, $analysis['available_credit']);
        $this->assertEquals(60.0, $analysis['credit_utilization_percent']);
        $this->assertArrayHasKey('payment_score', $analysis);
        $this->assertArrayHasKey('credit_status', $analysis);
        $this->assertArrayHasKey('risk_level', $analysis);
    }

    public function test_generates_aging_summary(): void
    {
        $customer = Contact::factory()->customer()->create();

        // Current
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        // 1-30 days overdue
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 2000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->subDays(15)->toDateString(),
        ]);

        // 31-60 days overdue
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 3000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->subDays(45)->toDateString(),
        ]);

        $aging = $this->service->getAgingSummary($customer);

        $this->assertEquals(1000, $aging['current']);
        $this->assertEquals(2000, $aging['1-30']);
        $this->assertEquals(3000, $aging['31-60']);
    }

    public function test_updates_customer_credit_status(): void
    {
        $customer = Contact::factory()->customer()->create([
            'credit_limit' => 10000,
            'current_credit' => 0,
            'metadata' => [],
        ]);

        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 9000,
            'paid_amount' => 0,
            'status' => 'posted',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->service->updateCreditStatus($customer->id);

        $customer->refresh();

        $this->assertArrayHasKey('credit_status', $customer->metadata);
        $this->assertArrayHasKey('risk_level', $customer->metadata);
        $this->assertArrayHasKey('last_credit_check', $customer->metadata);
        $this->assertEquals('warning', $customer->metadata['credit_status']); // 90% utilization
    }
}

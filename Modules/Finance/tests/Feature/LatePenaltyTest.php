<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Services\LatePenaltyService;
use Modules\Contacts\Models\Contact;
use Carbon\Carbon;

/**
 * FI-M001: Tests for Late Payment Penalty functionality.
 */
class LatePenaltyTest extends TestCase
{
    protected LatePenaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LatePenaltyService::class);
    }

    protected function createOverdueInvoice(int $daysOverdue = 30, float $amount = 1000.00): ARInvoice
    {
        $customer = Contact::factory()->customer()->create();

        return ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'invoice_date' => Carbon::now()->subDays($daysOverdue + 30),
            'due_date' => Carbon::now()->subDays($daysOverdue),
            'total_amount' => $amount,
            'paid_amount' => 0.00,
            'status' => 'sent',
        ]);
    }

    protected function createCurrentInvoice(float $amount = 1000.00): ARInvoice
    {
        $customer = Contact::factory()->customer()->create();

        return ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'invoice_date' => Carbon::now()->subDays(10),
            'due_date' => Carbon::now()->addDays(20),
            'total_amount' => $amount,
            'paid_amount' => 0.00,
            'status' => 'sent',
        ]);
    }

    // Service Tests

    public function test_service_calculates_penalty_for_overdue_invoice(): void
    {
        $invoice = $this->createOverdueInvoice(30, 1000.00);

        $penalty = $this->service->calculatePenalty($invoice);

        $this->assertTrue($penalty['is_overdue']);
        $this->assertGreaterThanOrEqual(30, $penalty['days_overdue']); // May be slightly more due to time
        $this->assertEquals(1000.00, $penalty['outstanding_amount']);
        $this->assertGreaterThan(0, $penalty['penalty_amount']);
        $this->assertArrayHasKey('calculation', $penalty);
    }

    public function test_service_returns_no_penalty_for_current_invoice(): void
    {
        $invoice = $this->createCurrentInvoice(1000.00);

        $penalty = $this->service->calculatePenalty($invoice);

        $this->assertFalse($penalty['is_overdue']);
        $this->assertEquals(0, $penalty['days_overdue']);
        $this->assertEquals(0.0, $penalty['penalty_amount']);
    }

    public function test_service_calculates_correct_penalty_amount(): void
    {
        // 1000 at 18% annual for ~30 days
        // Daily rate = 18/365 = 0.04932%
        // Penalty = 1000 * 0.0004932 * days ~= 14.79
        $invoice = $this->createOverdueInvoice(30, 1000.00);

        $penalty = $this->service->calculatePenalty($invoice, null, 18.0);

        // Use actual days from penalty calculation for expected value
        $expectedPenalty = 1000 * (18 / 365 / 100) * $penalty['days_overdue'];
        $this->assertEqualsWithDelta($expectedPenalty, $penalty['penalty_amount'], 0.50);
    }

    public function test_service_considers_paid_amount_in_calculation(): void
    {
        $customer = Contact::factory()->customer()->create();
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'due_date' => Carbon::now()->subDays(30),
            'total_amount' => 1000.00,
            'paid_amount' => 600.00, // 400 outstanding
            'status' => 'partial',
        ]);

        $penalty = $this->service->calculatePenalty($invoice);

        $this->assertEquals(400.00, $penalty['outstanding_amount']);
        // Penalty should be based on 400, not 1000
        $expectedPenalty = 400 * (18 / 365 / 100) * $penalty['days_overdue'];
        $this->assertEqualsWithDelta($expectedPenalty, $penalty['penalty_amount'], 0.50);
    }

    public function test_service_returns_no_overdue_for_paid_invoice(): void
    {
        $customer = Contact::factory()->customer()->create();
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'due_date' => Carbon::now()->subDays(30),
            'total_amount' => 1000.00,
            'paid_amount' => 1000.00,
            'status' => 'paid',
        ]);

        $this->assertFalse($this->service->isOverdue($invoice));
    }

    public function test_service_can_apply_penalty_to_invoice(): void
    {
        $invoice = $this->createOverdueInvoice(30, 1000.00);
        $originalTotal = $invoice->total_amount;

        $result = $this->service->applyPenalty($invoice, 50.00, 'Test penalty');

        $this->assertTrue($result['success']);
        $this->assertEquals(50.00, $result['penalty_applied']);
        $this->assertEquals($originalTotal + 50.00, $result['new_total']);

        $invoice->refresh();
        $this->assertEquals($originalTotal + 50.00, $invoice->total_amount);
        $this->assertArrayHasKey('penalties', $invoice->metadata);
    }

    public function test_service_cannot_apply_penalty_to_current_invoice(): void
    {
        $invoice = $this->createCurrentInvoice(1000.00);

        $result = $this->service->applyPenalty($invoice);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no está vencida', $result['message']);
    }

    public function test_service_gets_all_overdue_invoices(): void
    {
        $this->createOverdueInvoice(30, 1000.00);
        $this->createOverdueInvoice(60, 2000.00);
        $this->createCurrentInvoice(500.00);

        $overdueInvoices = $this->service->getOverdueInvoicesWithPenalties();

        $this->assertGreaterThanOrEqual(2, $overdueInvoices->count());
    }

    public function test_service_generates_aging_report(): void
    {
        // Create invoices in different aging buckets
        $this->createOverdueInvoice(15, 1000.00);  // 1-30 days
        $this->createOverdueInvoice(45, 2000.00);  // 31-60 days
        $this->createOverdueInvoice(75, 3000.00);  // 61-90 days

        $report = $this->service->getAgingReport();

        $this->assertArrayHasKey('buckets', $report);
        $this->assertCount(5, $report['buckets']); // 5 aging buckets
        $this->assertArrayHasKey('total_overdue_amount', $report);
    }

    // API Tests

    public function test_admin_can_get_late_penalty_for_invoice(): void
    {
        $user = $this->getAdminUser();
        $invoice = $this->createOverdueInvoice(30, 1000.00);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/ar-invoices/' . $invoice->id . '/late-penalty');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'invoice_id',
                'invoice_number',
                'is_overdue',
                'days_overdue',
                'outstanding_amount',
                'penalty_amount',
                'annual_rate',
            ],
        ]);
    }

    public function test_admin_can_apply_penalty_to_invoice(): void
    {
        $user = $this->getAdminUser();
        $invoice = $this->createOverdueInvoice(30, 1000.00);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/ar-invoices/' . $invoice->id . '/apply-penalty', [
                'penalty_amount' => 50.00,
                'notes' => 'Late payment penalty',
            ]);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'success' => true,
                'penalty_applied' => 50.00,
            ],
        ]);
    }

    public function test_admin_can_get_overdue_invoices_with_penalties(): void
    {
        $user = $this->getAdminUser();
        $this->createOverdueInvoice(30, 1000.00);
        $this->createOverdueInvoice(60, 2000.00);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/ar-invoices/overdue-with-penalties');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'as_of_date',
                'annual_rate',
                'total_invoices',
                'total_outstanding',
                'total_penalties',
                'invoices',
            ],
        ]);
    }

    public function test_admin_can_get_penalty_summary(): void
    {
        $user = $this->getAdminUser();
        $this->createOverdueInvoice(30, 1000.00);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/ar-invoices/penalty-summary');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'as_of_date',
                'customers',
            ],
        ]);
    }

    public function test_admin_can_get_aging_report(): void
    {
        $user = $this->getAdminUser();
        $this->createOverdueInvoice(30, 1000.00);
        $this->createOverdueInvoice(60, 2000.00);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/ar-invoices/aging-report');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'as_of_date',
                'total_overdue_invoices',
                'total_overdue_amount',
                'buckets',
            ],
        ]);
    }

    public function test_guest_cannot_access_penalty_endpoints(): void
    {
        $invoice = $this->createOverdueInvoice(30, 1000.00);

        $this->getJson('/api/v1/ar-invoices/' . $invoice->id . '/late-penalty')
            ->assertStatus(401);

        $this->getJson('/api/v1/ar-invoices/overdue-with-penalties')
            ->assertStatus(401);

        $this->getJson('/api/v1/ar-invoices/aging-report')
            ->assertStatus(401);
    }

    public function test_service_can_set_custom_rate(): void
    {
        $invoice = $this->createOverdueInvoice(30, 1000.00);

        $penalty18 = $this->service->calculatePenalty($invoice, null, 18.0);
        $penalty24 = $this->service->calculatePenalty($invoice, null, 24.0);

        $this->assertGreaterThan($penalty18['penalty_amount'], $penalty24['penalty_amount']);
    }

    public function test_multiple_penalties_accumulate(): void
    {
        $invoice = $this->createOverdueInvoice(30, 1000.00);

        // Apply first penalty
        $this->service->applyPenalty($invoice, 25.00, 'First penalty');
        $invoice->refresh();

        // Apply second penalty
        $this->service->applyPenalty($invoice, 30.00, 'Second penalty');
        $invoice->refresh();

        $this->assertCount(2, $invoice->metadata['penalties']);
        $this->assertEquals(55.00, $invoice->metadata['total_penalties']);
    }
}

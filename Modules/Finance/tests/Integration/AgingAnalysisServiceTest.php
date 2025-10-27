<?php

namespace Modules\Finance\Tests\Integration;

use Tests\TestCase;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Services\AgingAnalysisService;
use Modules\Contacts\Models\Contact;
use Carbon\Carbon;

/**
 * AgingAnalysisServiceTest
 *
 * Tests de integración para el servicio de análisis de antigüedad de saldos
 */
class AgingAnalysisServiceTest extends TestCase
{
    private AgingAnalysisService $agingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agingService = app(AgingAnalysisService::class);
    }

    public function test_generate_ar_aging_report(): void
    {
        // Arrange: Crear customer y 3 invoices con diferentes fechas de vencimiento
        $customer = Contact::factory()->customer()->create(['name' => 'Test Customer']);

        // Invoice vencida hace 45 días (bucket 31-60)
        $invoice1 = ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'invoice_number' => 'AR-001',
            'invoice_date' => now()->subDays(75),
            'due_date' => now()->subDays(45),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Invoice vencida hace 15 días (bucket 1-30)
        $invoice2 = ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'invoice_number' => 'AR-002',
            'invoice_date' => now()->subDays(45),
            'due_date' => now()->subDays(15),
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Invoice no vencida (bucket current)
        $invoice3 = ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'invoice_number' => 'AR-003',
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->addDays(20),
            'total_amount' => 300.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Act: Generar reporte
        $aging = $this->agingService->generateARAging();

        // Assert: Verificar que retorna 3 invoices
        $this->assertCount(3, $aging);

        // Assert: Verificar aging buckets
        $invoice1Data = $aging->firstWhere('invoice_number', 'AR-001');
        $this->assertEquals('31-60', $invoice1Data['aging_bucket']);
        $this->assertEquals(45, $invoice1Data['days_overdue']);

        $invoice2Data = $aging->firstWhere('invoice_number', 'AR-002');
        $this->assertEquals('1-30', $invoice2Data['aging_bucket']);
        $this->assertEquals(15, $invoice2Data['days_overdue']);

        $invoice3Data = $aging->firstWhere('invoice_number', 'AR-003');
        $this->assertEquals('current', $invoice3Data['aging_bucket']);
        $this->assertEquals(-20, $invoice3Data['days_overdue']);
    }

    public function test_ar_aging_excludes_paid_invoices(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();

        // Invoice no pagada
        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'invoice_number' => 'AR-001',
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Invoice completamente pagada (debe ser excluida)
        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'invoice_number' => 'AR-002',
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
            'status' => 'paid',
        ]);

        // Act
        $aging = $this->agingService->generateARAging();

        // Assert: Solo 1 invoice (la no pagada)
        $this->assertCount(1, $aging);
        $this->assertEquals('AR-001', $aging->first()['invoice_number']);
    }

    public function test_ar_aging_calculates_balance_correctly(): void
    {
        // Arrange: Invoice parcialmente pagada
        $customer = Contact::factory()->customer()->create();

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'invoice_number' => 'AR-001',
            'total_amount' => 1000.00,
            'paid_amount' => 300.00,
            'status' => 'partial',
        ]);

        // Act
        $aging = $this->agingService->generateARAging();

        // Assert: Balance debe ser 700.00
        $this->assertEquals(700.00, $aging->first()['balance_amount']);
    }

    public function test_generate_ar_aging_summary_by_contact(): void
    {
        // Arrange: 2 customers con múltiples invoices
        $customer1 = Contact::factory()->customer()->create(['name' => 'Customer A']);
        $customer2 = Contact::factory()->customer()->create(['name' => 'Customer B']);

        // Customer 1: 2 invoices vencidas
        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer1->id,
            'due_date' => now()->subDays(15),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer1->id,
            'due_date' => now()->subDays(45),
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Customer 2: 1 invoice vencida
        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer2->id,
            'due_date' => now()->subDays(10),
            'total_amount' => 2000.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Act
        $summary = $this->agingService->generateARAgingSummary();

        // Assert: 2 contactos en el summary
        $this->assertCount(2, $summary);

        // Assert: Customer 2 primero (mayor balance)
        $this->assertEquals($customer2->id, $summary->first()['contact_id']);
        $this->assertEquals(2000.00, $summary->first()['total_balance']);

        // Assert: Customer 1 segundo
        $customer1Summary = $summary->firstWhere('contact_id', $customer1->id);
        $this->assertEquals(1500.00, $customer1Summary['total_balance']);
        $this->assertEquals(2, $customer1Summary['invoice_count']);
    }

    public function test_generate_ap_aging_report(): void
    {
        // Arrange: Crear supplier y 2 invoices
        $supplier = Contact::factory()->supplier()->create(['name' => 'Test Supplier']);

        // Invoice vencida hace 95 días (bucket 90+)
        APInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $supplier->id,
            'invoice_number' => 'AP-001',
            'invoice_date' => now()->subDays(125),
            'due_date' => now()->subDays(95),
            'total_amount' => 5000.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Invoice no vencida (bucket current)
        APInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $supplier->id,
            'invoice_number' => 'AP-002',
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->addDays(20),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Act
        $aging = $this->agingService->generateAPAging();

        // Assert
        $this->assertCount(2, $aging);

        $invoice1Data = $aging->firstWhere('invoice_number', 'AP-001');
        $this->assertEquals('90+', $invoice1Data['aging_bucket']);
        $this->assertEquals(95, $invoice1Data['days_overdue']);

        $invoice2Data = $aging->firstWhere('invoice_number', 'AP-002');
        $this->assertEquals('current', $invoice2Data['aging_bucket']);
    }

    public function test_ar_aging_with_as_of_date(): void
    {
        // Arrange: Invoice que vence en 10 días desde HOY
        $customer = Contact::factory()->customer()->create();

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'invoice_number' => 'AR-001',
            'invoice_date' => now()->subDays(20),
            'due_date' => now()->addDays(10),
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Act: Generar aging con fecha 15 días en el futuro
        $asOfDate = now()->addDays(15);
        $aging = $this->agingService->generateARAging($asOfDate);

        // Assert: Desde esa fecha, la invoice está vencida hace 5 días (bucket 1-30)
        $this->assertEquals('1-30', $aging->first()['aging_bucket']);
        $this->assertEquals(5, $aging->first()['days_overdue']);
    }

    public function test_ar_aging_filters_by_contact_id(): void
    {
        // Arrange: 2 customers
        $customer1 = Contact::factory()->customer()->create();
        $customer2 = Contact::factory()->customer()->create();

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer1->id,
            'invoice_number' => 'AR-001',
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer2->id,
            'invoice_number' => 'AR-002',
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Act: Filtrar solo customer 1
        $aging = $this->agingService->generateARAging(null, $customer1->id);

        // Assert: Solo 1 invoice (del customer 1)
        $this->assertCount(1, $aging);
        $this->assertEquals('AR-001', $aging->first()['invoice_number']);
        $this->assertEquals($customer1->id, $aging->first()['contact_id']);
    }

    public function test_get_total_ar_balance(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'total_amount' => 1000.00,
            'paid_amount' => 300.00,
            'status' => 'partial',
        ]);

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Invoice pagada (no debe contar)
        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer->id,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'status' => 'paid',
        ]);

        // Act
        $totalBalance = $this->agingService->getTotalARBalance();

        // Assert: (1000 - 300) + (500 - 0) = 1200.00
        $this->assertEquals(1200.00, $totalBalance);
    }

    public function test_get_contact_ar_balance(): void
    {
        // Arrange: 2 customers
        $customer1 = Contact::factory()->customer()->create();
        $customer2 = Contact::factory()->customer()->create();

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer1->id,
            'total_amount' => 1000.00,
            'paid_amount' => 200.00,
            'status' => 'partial',
        ]);

        ARInvoice::create([
            'currency' => 'MXN',
            'subtotal' => 0,
            'tax_amount' => 0,
            'is_active' => true,
            'contact_id' => $customer2->id,
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'status' => 'posted',
        ]);

        // Act
        $customer1Balance = $this->agingService->getContactARBalance($customer1->id);

        // Assert: 1000 - 200 = 800.00
        $this->assertEquals(800.00, $customer1Balance);
    }
}

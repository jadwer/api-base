<?php

namespace Modules\Finance\Tests\Integration;

use Tests\TestCase;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Events\SalesOrderCompleted;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Events\PurchaseOrderReceived;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Illuminate\Support\Facades\Event;

/**
 * EventDrivenIntegrationTest
 *
 * Tests for event-driven integration between Sales/Purchase and Finance modules
 *
 * Fase C: Event Listeners Cross-Module
 * - C.1: SalesOrderCompletedListener
 * - C.2: PurchaseOrderReceivedListener
 */
class EventDrivenIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that SalesOrderCompleted event creates AR Invoice
     *
     * Flow: Sales Order Completed → Create AR Invoice → Update Sales Order
     *
     * NOTE: This test is superseded by OnlineSalesE2ETest which has full setup
     */
    public function test_sales_order_completed_creates_ar_invoice(): void
    {
        $this->markTestSkipped('Use OnlineSalesE2ETest::test_ar_invoice_created_from_sales_order_event instead');

        // Arrange: Create customer contact
        $customer = Contact::factory()->create([
            'is_customer' => true,
            'credit_limit' => 100000,
            'credit_status' => 'active',
        ]);

        // Create product
        $product = Product::factory()->create([
            'price' => 100.00,
        ]);

        // Create sales order with items
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'processing',
            'total_amount' => 1160.00,
            'ar_invoice_id' => null,
        ]);

        // Create sales order items
        SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
            'discount' => 0,
            'total' => 1000.00,
        ]);

        // Act: Fire the event
        event(new SalesOrderCompleted($salesOrder->fresh()));

        // Assert: AR Invoice was created
        $salesOrder->refresh();

        $this->assertNotNull($salesOrder->ar_invoice_id, 'AR Invoice should be created');

        $arInvoice = ARInvoice::find($salesOrder->ar_invoice_id);
        $this->assertNotNull($arInvoice);
        $this->assertEquals($customer->id, $arInvoice->contact_id);
        $this->assertEquals('invoiced', $salesOrder->invoicing_status);
    }

    /**
     * Test that PurchaseOrderReceived event creates AP Invoice
     *
     * Flow: Purchase Order Received → Create AP Invoice → Update Purchase Order
     *
     * @see ProcureToPayE2ETest (future v1.2 implementation)
     */
    public function test_purchase_order_received_creates_ap_invoice(): void
    {
        $this->markTestSkipped('Procure-to-Pay E2E test planned for v1.2');

        // Arrange: Create supplier contact
        $supplier = Contact::factory()->create([
            'is_supplier' => true,
        ]);

        // Create product
        $product = Product::factory()->create([
            'cost' => 50.00,
        ]);

        // Create purchase order
        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'status' => 'received',
            'approval_status' => 'approved',
            'total_amount' => 580.00,
            'ap_invoice_id' => null,
        ]);

        // Create purchase order items
        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 50.00,
            'discount' => 0,
            'subtotal' => 500.00,
            'total' => 500.00,
        ]);

        // Act: Fire the event
        event(new PurchaseOrderReceived($purchaseOrder->fresh()));

        // Assert: AP Invoice was created
        $purchaseOrder->refresh();

        $this->assertNotNull($purchaseOrder->ap_invoice_id, 'AP Invoice should be created');

        $apInvoice = APInvoice::find($purchaseOrder->ap_invoice_id);
        $this->assertNotNull($apInvoice);
        $this->assertEquals($supplier->id, $apInvoice->contact_id);
        $this->assertEquals('invoiced', $purchaseOrder->invoicing_status);
    }

    /**
     * Test that duplicate event does not create duplicate invoice
     *
     * Idempotency: If order already has invoice, skip creation
     */
    public function test_duplicate_event_does_not_create_duplicate_invoice(): void
    {
        // Arrange: Create customer and sales order with existing invoice
        $customer = Contact::factory()->create(['is_customer' => true]);
        $product = Product::factory()->create();

        $existingInvoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000.00,
        ]);

        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'ar_invoice_id' => $existingInvoice->id,
            'total_amount' => 1000.00,
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
        ]);

        $invoiceCountBefore = ARInvoice::count();

        // Act: Fire the event again
        event(new SalesOrderCompleted($salesOrder));

        // Assert: No new invoice created
        $invoiceCountAfter = ARInvoice::count();
        $this->assertEquals($invoiceCountBefore, $invoiceCountAfter, 'No duplicate invoice should be created');

        // Original invoice ID should remain
        $salesOrder->refresh();
        $this->assertEquals($existingInvoice->id, $salesOrder->ar_invoice_id);
    }

    /**
     * Test complete Order-to-Cash flow
     *
     * NOTE: Superseded by OnlineSalesE2ETest::test_complete_online_sales_flow
     */
    public function test_order_to_cash_complete_flow(): void
    {
        $this->markTestSkipped('Use OnlineSalesE2ETest::test_complete_online_sales_flow instead');

        // Arrange
        $customer = Contact::factory()->create([
            'is_customer' => true,
            'credit_limit' => 50000,
        ]);

        $product = Product::factory()->create(['price' => 200.00]);

        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'completed',
            'total_amount' => 2320.00,
            'ar_invoice_id' => null,
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 200.00,
            'discount' => 0,
            'total' => 2000.00,
        ]);

        // Act
        event(new SalesOrderCompleted($salesOrder->fresh()));

        // Assert
        $salesOrder->refresh();

        $this->assertNotNull($salesOrder->ar_invoice_id);
        $this->assertEquals('invoiced', $salesOrder->invoicing_status);

        $arInvoice = ARInvoice::find($salesOrder->ar_invoice_id);
        $this->assertNotNull($arInvoice);
        $this->assertStringContainsString('Auto-generated from Sales Order', $arInvoice->notes ?? '');
    }

    /**
     * Test complete Procure-to-Pay flow
     *
     * @see ProcureToPayE2ETest (future v1.2 implementation)
     */
    public function test_procure_to_pay_complete_flow(): void
    {
        $this->markTestSkipped('Procure-to-Pay E2E test planned for v1.2');

        // Arrange
        $supplier = Contact::factory()->create(['is_supplier' => true]);
        $product = Product::factory()->create(['cost' => 75.00]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'status' => 'received',
            'approval_status' => 'approved',
            'total_amount' => 870.00,
            'ap_invoice_id' => null,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 75.00,
            'discount' => 0,
            'subtotal' => 750.00,
            'total' => 750.00,
        ]);

        // Act
        event(new PurchaseOrderReceived($purchaseOrder->fresh()));

        // Assert
        $purchaseOrder->refresh();

        $this->assertNotNull($purchaseOrder->ap_invoice_id);
        $this->assertEquals('invoiced', $purchaseOrder->invoicing_status);

        $apInvoice = APInvoice::find($purchaseOrder->ap_invoice_id);
        $this->assertNotNull($apInvoice);
        $this->assertStringContainsString('Auto-generated from Purchase Order', $apInvoice->notes ?? '');
    }
}

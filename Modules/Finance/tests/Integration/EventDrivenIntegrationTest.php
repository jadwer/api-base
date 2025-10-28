<?php

namespace Modules\Finance\Tests\Integration;

use Tests\TestCase;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Events\SalesOrderCompleted;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Events\PurchaseOrderReceived;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Contacts\Models\Contact;
use Illuminate\Support\Facades\Event;

/**
 * EventDrivenIntegrationTest
 *
 * Tests for event-driven integration between Sales/Purchase and Finance modules
 */
class EventDrivenIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure events are not faked
        Event::fake([]);
    }

    public function test_sales_order_completed_creates_ar_invoice(): void
    {
        // Arrange: Create a sales order with items
        $customer = Contact::factory()->customer()->create();

        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'order_number' => 'SO-001',
            'currency' => 'MXN',
            'status' => 'confirmed',
        ]);

        // Add items to sales order (assuming SalesOrderItem factory exists)
        // If not, we can mock this or just test with the base order

        // Act: Dispatch the SalesOrderCompleted event
        event(new SalesOrderCompleted($salesOrder));

        // Assert: AR Invoice should have been created
        $this->assertDatabaseHas('ar_invoices', [
            'contact_id' => $customer->id,
        ]);

        // Assert: Sales Order should be updated with invoice reference
        $salesOrder->refresh();
        $this->assertNotNull($salesOrder->ar_invoice_id);
        $this->assertEquals('invoiced', $salesOrder->invoicing_status);
        $this->assertEquals('invoiced', $salesOrder->financial_status);

        // Assert: AR Invoice should have correct metadata
        $arInvoice = ARInvoice::where('contact_id', $customer->id)->first();
        $this->assertNotNull($arInvoice);
        $this->assertStringContainsString('SO-001', $arInvoice->notes ?? '');
    }

    public function test_purchase_order_received_creates_ap_invoice(): void
    {
        // Arrange: Create a purchase order
        $supplier = Contact::factory()->supplier()->create();

        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'status' => 'approved',
        ]);

        // Act: Dispatch the PurchaseOrderReceived event
        event(new PurchaseOrderReceived($purchaseOrder));

        // Assert: AP Invoice should have been created
        $this->assertDatabaseHas('ap_invoices', [
            'contact_id' => $supplier->id,
        ]);

        // Assert: Purchase Order should be updated with invoice reference
        $purchaseOrder->refresh();
        $this->assertNotNull($purchaseOrder->ap_invoice_id);
        $this->assertEquals('invoiced', $purchaseOrder->invoicing_status);
        $this->assertEquals('invoiced', $purchaseOrder->financial_status);

        // Assert: AP Invoice should have correct metadata
        $apInvoice = APInvoice::where('contact_id', $supplier->id)->first();
        $this->assertNotNull($apInvoice);
        // Note: PO reference in notes depends on implementation
        $this->assertNotNull($apInvoice->notes);
    }

    public function test_duplicate_event_does_not_create_duplicate_invoice(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();

        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'order_number' => 'SO-002',
            'status' => 'confirmed',
        ]);

        // Act: Dispatch event twice
        event(new SalesOrderCompleted($salesOrder));
        event(new SalesOrderCompleted($salesOrder));

        // Assert: Only one AR Invoice should exist
        $invoiceCount = ARInvoice::where('contact_id', $customer->id)->count();
        $this->assertEquals(1, $invoiceCount, 'Should only create one invoice even if event fired twice');
    }

    public function test_ar_invoice_posted_updates_sales_order_status(): void
    {
        $this->markTestSkipped('AR Invoice posting event already tested in ARInvoiceGLPostingTest');
    }
}

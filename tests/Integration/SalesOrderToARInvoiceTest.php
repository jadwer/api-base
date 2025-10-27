<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Events\SalesOrderCompleted;
use Modules\Sales\Events\SalesOrderCancelled;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\ARInvoiceLine;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;

class SalesOrderToARInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed necessary data
        $this->seed(\Modules\PermissionManager\Database\Seeders\RoleSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\AssignPermissionsSeeder::class);
    }

    /** @test */
    public function sales_order_completed_creates_ar_invoice()
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $product = Product::factory()->create(['unit_price' => 100]);

        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'pending',
            'total_amount' => 1000,
            'subtotal' => 1000,
            'tax_amount' => 0
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 100,
            'total' => 1000
        ]);

        // Act
        event(new SalesOrderCompleted($salesOrder));

        // Assert: AR Invoice was created
        $this->assertDatabaseHas('ar_invoices', [
            'sales_order_id' => $salesOrder->id,
            'contact_id' => $customer->id,
            'total_amount' => 1000
        ]);

        // Assert: Sales Order was synchronized
        $salesOrder->refresh();
        $this->assertNotNull($salesOrder->ar_invoice_id);
        $this->assertEquals('complete', $salesOrder->invoicing_status);
        $this->assertEquals('not_invoiced', $salesOrder->financial_status);

        // Assert: AR Invoice Lines were created
        $arInvoice = ARInvoice::where('sales_order_id', $salesOrder->id)->first();
        $this->assertCount(1, $arInvoice->aRInvoiceLines);

        $line = $arInvoice->aRInvoiceLines->first();
        $this->assertEquals($product->id, $line->product_id);
        $this->assertEquals(10, $line->quantity);
        $this->assertEquals(100, $line->unit_price);
    }

    /** @test */
    public function sales_order_completed_is_idempotent()
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000
        ]);

        // Act: Trigger event twice
        event(new SalesOrderCompleted($salesOrder));
        event(new SalesOrderCompleted($salesOrder));

        // Assert: Only one AR Invoice was created
        $invoices = ARInvoice::where('sales_order_id', $salesOrder->id)->get();
        $this->assertCount(1, $invoices);
    }

    /** @test */
    public function sales_order_cancelled_voids_draft_ar_invoice()
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000
        ]);

        // Create AR Invoice first
        event(new SalesOrderCompleted($salesOrder));

        $arInvoice = ARInvoice::where('sales_order_id', $salesOrder->id)->first();
        $this->assertEquals('draft', $arInvoice->status);

        // Act: Cancel Sales Order
        event(new SalesOrderCancelled($salesOrder));

        // Assert: AR Invoice was voided
        $arInvoice->refresh();
        $this->assertEquals('voided', $arInvoice->status);

        // Assert: Sales Order status updated
        $salesOrder->refresh();
        $this->assertEquals('cancelled', $salesOrder->invoicing_status);
        $this->assertEquals('cancelled', $salesOrder->financial_status);
    }

    /** @test */
    public function sales_order_cancelled_does_not_void_posted_ar_invoice()
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000
        ]);

        // Create and post AR Invoice
        event(new SalesOrderCompleted($salesOrder));

        $arInvoice = ARInvoice::where('sales_order_id', $salesOrder->id)->first();
        $arInvoice->update(['status' => 'posted']);

        // Act: Cancel Sales Order
        event(new SalesOrderCancelled($salesOrder));

        // Assert: AR Invoice status unchanged
        $arInvoice->refresh();
        $this->assertEquals('posted', $arInvoice->status);
    }

    /** @test */
    public function sales_order_completed_generates_unique_invoice_numbers()
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();

        $salesOrder1 = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000
        ]);

        $salesOrder2 = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 2000
        ]);

        // Act
        event(new SalesOrderCompleted($salesOrder1));
        event(new SalesOrderCompleted($salesOrder2));

        // Assert: Unique invoice numbers
        $invoice1 = ARInvoice::where('sales_order_id', $salesOrder1->id)->first();
        $invoice2 = ARInvoice::where('sales_order_id', $salesOrder2->id)->first();

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
        $this->assertStringStartsWith('AR-', $invoice1->invoice_number);
        $this->assertStringStartsWith('AR-', $invoice2->invoice_number);
    }
}

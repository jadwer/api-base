<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\OrderStatusService;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Contacts\Models\Contact;
use Illuminate\Support\Facades\Log;

/**
 * SA-004: Inventory Reservation Business Rule Tests
 *
 * Tests that inventory is properly reserved when orders are confirmed
 * and released when orders are cancelled.
 */
class SalesOrderInventoryReservationTest extends TestCase
{
    private OrderStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderStatusService::class);
    }

    /**
     * Test inventory is reserved when order status changes to confirmed
     */
    public function test_inventory_reserved_when_order_confirmed(): void
    {
        // Arrange: Create product, warehouse, and stock
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100.0,
            'reserved_quantity' => 0.0,
        ]);

        $contact = Contact::factory()->create(['is_customer' => true]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 25.0,
            'unit_price' => 10.00,
        ]);

        // Act: Confirm order
        $this->service->updateStatus($order, 'confirmed');

        // Assert: Stock quantities updated (only reserved_quantity changes, quantity stays the same)
        $stock->refresh();
        $this->assertEquals(100.0, $stock->quantity); // unchanged
        $this->assertEquals(25.0, $stock->reserved_quantity);
    }

    /**
     * Test inventory is released when order is cancelled
     */
    public function test_inventory_released_when_order_cancelled(): void
    {
        // Arrange: Create order with reserved inventory
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 75.0,
            'reserved_quantity' => 25.0, // Already reserved
        ]);

        $contact = Contact::factory()->create(['is_customer' => true]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'confirmed',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 25.0,
            'unit_price' => 10.00,
        ]);

        // Act: Cancel order
        $this->service->updateStatus($order, 'cancelled');

        // Assert: Stock quantities released (only reserved_quantity changes, quantity stays the same)
        $stock->refresh();
        $this->assertEquals(75.0, $stock->quantity); // unchanged
        $this->assertEquals(0.0, $stock->reserved_quantity); // 25 - 25
    }

    /**
     * Test reservation with multiple items
     */
    public function test_reservation_with_multiple_items(): void
    {
        // Arrange
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $stock1 = Stock::factory()->create([
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50.0,
            'reserved_quantity' => 0.0,
        ]);

        $stock2 = Stock::factory()->create([
            'product_id' => $product2->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 80.0,
            'reserved_quantity' => 0.0,
        ]);

        $contact = Contact::factory()->create(['is_customer' => true]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 15.0,
            'unit_price' => 20.00,
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 30.0,
            'unit_price' => 15.00,
        ]);

        // Act
        $this->service->updateStatus($order, 'confirmed');

        // Assert both stocks reserved
        $stock1->refresh();
        $stock2->refresh();

        $this->assertEquals(50.0, $stock1->quantity); // unchanged
        $this->assertEquals(15.0, $stock1->reserved_quantity);

        $this->assertEquals(80.0, $stock2->quantity); // unchanged
        $this->assertEquals(30.0, $stock2->reserved_quantity);
    }

    /**
     * Test insufficient stock throws exception
     */
    public function test_insufficient_stock_throws_exception(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10.0, // Only 10 available
            'reserved_quantity' => 0.0,
        ]);

        $contact = Contact::factory()->create(['is_customer' => true]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 25.0, // Requesting 25, only 10 available
            'unit_price' => 10.00,
        ]);

        // Expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock for product ID');

        // Act
        $this->service->updateStatus($order, 'confirmed');
    }

    /**
     * Test reservation is atomic - all or nothing
     */
    public function test_reservation_is_atomic(): void
    {
        // Arrange: Two products, second has insufficient stock
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $stock1 = Stock::factory()->create([
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100.0,
            'reserved_quantity' => 0.0,
        ]);

        $stock2 = Stock::factory()->create([
            'product_id' => $product2->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5.0, // Insufficient
            'reserved_quantity' => 0.0,
        ]);

        $contact = Contact::factory()->create(['is_customer' => true]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 20.0,
            'unit_price' => 10.00,
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 10.0, // More than available
            'unit_price' => 15.00,
        ]);

        // Act & Assert
        try {
            $this->service->updateStatus($order, 'confirmed');
            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            // Expected
        }

        // Assert: Transaction rolled back - first product NOT reserved
        $stock1->refresh();
        $stock2->refresh();

        $this->assertEquals(100.0, $stock1->quantity); // Unchanged
        $this->assertEquals(0.0, $stock1->reserved_quantity); // Unchanged

        $this->assertEquals(5.0, $stock2->quantity); // Unchanged
        $this->assertEquals(0.0, $stock2->reserved_quantity); // Unchanged

        // Order status should not have changed
        $order->refresh();
        $this->assertEquals('draft', $order->status);
    }

    /**
     * Test full order lifecycle: pending → confirmed → cancelled
     */
    public function test_full_order_lifecycle(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100.0,
            'reserved_quantity' => 0.0,
        ]);

        $contact = Contact::factory()->create(['is_customer' => true]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 30.0,
            'unit_price' => 10.00,
        ]);

        // Step 1: Confirm order - reserves inventory (only reserved_quantity changes)
        $this->service->updateStatus($order, 'confirmed');

        $stock->refresh();
        $this->assertEquals(100.0, $stock->quantity); // unchanged
        $this->assertEquals(30.0, $stock->reserved_quantity);

        // Step 2: Cancel order - releases inventory (only reserved_quantity changes back)
        $this->service->updateStatus($order, 'cancelled');

        $stock->refresh();
        $this->assertEquals(100.0, $stock->quantity); // unchanged
        $this->assertEquals(0.0, $stock->reserved_quantity);
    }

    /**
     * Test reservation prevents overselling
     */
    public function test_reservation_prevents_overselling(): void
    {
        // Arrange: Create stock with limited quantity
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50.0,
            'reserved_quantity' => 0.0,
        ]);

        $contact = Contact::factory()->create(['is_customer' => true]);

        // First order takes 30 units
        $order1 = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order1->id,
            'product_id' => $product->id,
            'quantity' => 30.0,
            'unit_price' => 10.00,
        ]);

        $this->service->updateStatus($order1, 'confirmed');

        // Stock now: quantity unchanged, 30 reserved, available = 50 - 30 = 20
        $stock->refresh();
        $this->assertEquals(50.0, $stock->quantity); // unchanged
        $this->assertEquals(30.0, $stock->reserved_quantity);

        // Second order tries to take 25 units (more than available)
        $order2 = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order2->id,
            'product_id' => $product->id,
            'quantity' => 25.0,
            'unit_price' => 10.00,
        ]);

        // Should fail - preventing overselling
        $this->expectException(\Exception::class);
        $this->service->updateStatus($order2, 'confirmed');
    }

    /**
     * Test release handles missing stock gracefully
     */
    public function test_release_handles_missing_stock_gracefully(): void
    {
        // Arrange: Order without corresponding stock
        $product = Product::factory()->create();
        $contact = Contact::factory()->create(['is_customer' => true]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'confirmed',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10.0,
            'unit_price' => 10.00,
        ]);

        // Act: Cancel order (no stock exists, should log warning but not fail)
        Log::shouldReceive('warning')
            ->once()
            ->with('Stock not found for release', \Mockery::type('array'));

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        // Should not throw exception
        $updatedOrder = $this->service->updateStatus($order, 'cancelled');

        // Assert: Order cancelled successfully despite missing stock
        $this->assertEquals('cancelled', $updatedOrder->status);
    }
}

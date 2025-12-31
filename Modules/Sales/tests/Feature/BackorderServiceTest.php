<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\Stock;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\BackorderService;
use InvalidArgumentException;

/**
 * SA-M002: Tests for BackorderService business logic.
 */
class BackorderServiceTest extends TestCase
{
    protected BackorderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BackorderService::class);
    }

    protected function createOrderWithItem(float $quantity = 10): array
    {
        $customer = Contact::factory()->customer()->create();
        $product = Product::factory()->create();

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $orderItem = SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return [$order, $orderItem, $product, $customer];
    }

    public function test_can_create_backorder(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $backorder = $this->service->createBackorder($order, $orderItem, 5, [
            'priority' => 'high',
        ]);

        $this->assertNotNull($backorder);
        $this->assertEquals('pending', $backorder->status);
        $this->assertEquals('high', $backorder->priority);
        $this->assertEquals(10, $backorder->original_quantity);
        $this->assertEquals(5, $backorder->backorder_quantity);
        $this->assertEquals(0, $backorder->fulfilled_quantity);
        $this->assertStringStartsWith('BO-', $backorder->backorder_number);
    }

    public function test_cannot_create_backorder_with_zero_quantity(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Backorder quantity must be greater than zero');

        $this->service->createBackorder($order, $orderItem, 0);
    }

    public function test_cannot_create_backorder_with_negative_quantity(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Backorder quantity must be greater than zero');

        $this->service->createBackorder($order, $orderItem, -5);
    }

    public function test_can_fulfill_backorder(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $backorder = $this->service->createBackorder($order, $orderItem, 5);

        $fulfilled = $this->service->fulfillBackorder($backorder, 3);

        $this->assertEquals(3, $fulfilled->fulfilled_quantity);
        $this->assertEquals(2, $fulfilled->remaining_quantity);
        $this->assertEquals('partially_fulfilled', $fulfilled->status);
    }

    public function test_backorder_fulfilled_when_quantity_complete(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $backorder = $this->service->createBackorder($order, $orderItem, 5);

        $fulfilled = $this->service->fulfillBackorder($backorder, 5);

        $this->assertEquals(5, $fulfilled->fulfilled_quantity);
        $this->assertEquals(0, $fulfilled->remaining_quantity);
        $this->assertEquals('fulfilled', $fulfilled->status);
    }

    public function test_cannot_fulfill_more_than_remaining(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $backorder = $this->service->createBackorder($order, $orderItem, 5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot fulfill more than remaining quantity');

        $this->service->fulfillBackorder($backorder, 10);
    }

    public function test_cannot_fulfill_cancelled_backorder(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $backorder = $this->service->createBackorder($order, $orderItem, 5);
        $this->service->cancelBackorder($backorder);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot fulfill a cancelled backorder');

        $this->service->fulfillBackorder($backorder->fresh(), 3);
    }

    public function test_can_cancel_backorder(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $backorder = $this->service->createBackorder($order, $orderItem, 5);

        $cancelled = $this->service->cancelBackorder($backorder, 'Customer request');

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals('Customer request', $cancelled->notes);
    }

    public function test_cannot_cancel_fulfilled_backorder(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $backorder = $this->service->createBackorder($order, $orderItem, 5);
        $this->service->fulfillBackorder($backorder, 5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cancel a fulfilled backorder');

        $this->service->cancelBackorder($backorder->fresh());
    }

    public function test_get_order_backorder_summary(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $this->service->createBackorder($order, $orderItem, 5);
        $backorder2 = $this->service->createBackorder($order, $orderItem, 3);
        $this->service->fulfillBackorder($backorder2, 2);

        $summary = $this->service->getOrderBackorderSummary($order);

        $this->assertEquals($order->id, $summary['order_id']);
        $this->assertEquals(2, $summary['total_backorders']);
        $this->assertEquals(8, $summary['total_backordered']); // 5 + 3
        $this->assertEquals(2, $summary['total_fulfilled']);
        $this->assertEquals(6, $summary['total_remaining']); // 5 + (3-2)
    }

    public function test_get_pending_backorders_for_product(): void
    {
        [$order1, $orderItem1, $product] = $this->createOrderWithItem(10);
        [$order2, $orderItem2, $product2] = $this->createOrderWithItem(5);

        $this->service->createBackorder($order1, $orderItem1, 5);
        $this->service->createBackorder($order1, $orderItem1, 3);

        $pending = $this->service->getPendingBackordersForProduct($product->id);

        $this->assertCount(2, $pending);
    }

    public function test_fulfill_backorders_for_product(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $this->service->createBackorder($order, $orderItem, 5);
        $this->service->createBackorder($order, $orderItem, 3);

        // Fulfill with 6 units available - should fulfill first (5) + partial of second (1)
        $result = $this->service->fulfillBackordersForProduct($product->id, null, 6);

        $this->assertCount(2, $result); // Both backorders touched
        $this->assertEquals(5, $result[0]['fulfilled_quantity']);
        $this->assertEquals(1, $result[1]['fulfilled_quantity']);
    }

    public function test_fulfill_backorders_respects_priority(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(20);

        // Create low priority first
        $lowPriority = $this->service->createBackorder($order, $orderItem, 5, [
            'priority' => 'low',
        ]);

        // Create urgent priority second
        $urgentPriority = $this->service->createBackorder($order, $orderItem, 3, [
            'priority' => 'urgent',
        ]);

        // Fulfill with 4 units - should prioritize urgent
        $result = $this->service->fulfillBackordersForProduct($product->id, null, 4);

        // Urgent should be fully fulfilled (3 units)
        $urgentPriority->refresh();
        $this->assertEquals(3, $urgentPriority->fulfilled_quantity);
        $this->assertEquals('fulfilled', $urgentPriority->status);

        // Low priority should get remaining (1 unit)
        $lowPriority->refresh();
        $this->assertEquals(1, $lowPriority->fulfilled_quantity);
        $this->assertEquals('partially_fulfilled', $lowPriority->status);
    }

    public function test_process_order_for_backorders_with_sufficient_stock(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);
        $warehouse = Warehouse::factory()->create();

        // Create sufficient stock
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20,
        ]);

        $backorders = $this->service->processOrderForBackorders($order, $warehouse->id);

        $this->assertEmpty($backorders);
    }

    public function test_process_order_for_backorders_with_insufficient_stock(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);
        $warehouse = Warehouse::factory()->create();

        // Create insufficient stock
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
        ]);

        $backorders = $this->service->processOrderForBackorders($order, $warehouse->id);

        $this->assertCount(1, $backorders);
        $this->assertEquals(7, $backorders[0]->backorder_quantity); // 10 - 3 = 7
    }

    public function test_partial_fulfillment_updates_status_correctly(): void
    {
        [$order, $orderItem, $product] = $this->createOrderWithItem(10);

        $backorder = $this->service->createBackorder($order, $orderItem, 10);

        // First fulfillment
        $this->service->fulfillBackorder($backorder, 3);
        $backorder->refresh();
        $this->assertEquals('partially_fulfilled', $backorder->status);

        // Second fulfillment
        $this->service->fulfillBackorder($backorder, 4);
        $backorder->refresh();
        $this->assertEquals('partially_fulfilled', $backorder->status);

        // Final fulfillment
        $this->service->fulfillBackorder($backorder, 3);
        $backorder->refresh();
        $this->assertEquals('fulfilled', $backorder->status);
    }
}

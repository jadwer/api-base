<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\Shipment;
use Modules\Sales\Services\ShipmentService;
use InvalidArgumentException;

/**
 * SA-M001: Tests for ShipmentService business logic.
 */
class ShipmentServiceTest extends TestCase
{
    protected ShipmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShipmentService::class);
    }

    public function test_can_create_shipment_for_order(): void
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
            'quantity' => 10,
        ]);

        $shipment = $this->service->createShipment($order, [
            $orderItem->id => 5,
        ], [
            'carrier' => 'FedEx',
            'tracking_number' => 'FEDEX123',
        ]);

        $this->assertNotNull($shipment);
        $this->assertEquals('pending', $shipment->status);
        $this->assertEquals('FedEx', $shipment->carrier);
        $this->assertCount(1, $shipment->items);
        $this->assertEquals(5, $shipment->items->first()->quantity);
    }

    public function test_cannot_create_shipment_for_draft_order(): void
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->draft()->create([
            'contact_id' => $customer->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order must be in confirmed, processing, or shipped status');

        $this->service->createShipment($order, [], []);
    }

    public function test_cannot_ship_more_than_remaining_quantity(): void
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
            'quantity' => 10,
            'shipped_quantity' => 0,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot ship 15 of item');

        $this->service->createShipment($order, [
            $orderItem->id => 15,
        ]);
    }

    public function test_can_mark_shipment_as_shipped(): void
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
            'quantity' => 10,
            'shipped_quantity' => 0,
        ]);

        $shipment = $this->service->createShipment($order, [
            $orderItem->id => 10,
        ]);

        $shipped = $this->service->markAsShipped($shipment, 'TRACK123', 'UPS');

        $this->assertEquals('shipped', $shipped->status);
        $this->assertEquals('TRACK123', $shipped->tracking_number);
        $this->assertEquals('UPS', $shipped->carrier);
        $this->assertNotNull($shipped->ship_date);

        // Verify order item was updated
        $orderItem->refresh();
        $this->assertEquals(10, $orderItem->shipped_quantity);
        $this->assertEquals('shipped', $orderItem->fulfillment_status);
    }

    public function test_can_mark_shipment_as_delivered(): void
    {
        $customer = Contact::factory()->customer()->create();
        $product = Product::factory()->create();

        // Create order without observer side effects by setting invoicing_status directly
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
            'invoicing_status' => 'not_invoiced', // Valid enum value
        ]);

        $orderItem = SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'shipped_quantity' => 0,
        ]);

        $shipment = $this->service->createShipment($order, [
            $orderItem->id => 10,
        ]);

        $this->service->markAsShipped($shipment);

        // Refresh to get latest state and temporarily disable observers for delivery marking
        $shipmentFresh = $shipment->fresh();
        $delivered = $this->service->markAsDelivered($shipmentFresh);

        $this->assertEquals('delivered', $delivered->status);
        $this->assertNotNull($delivered->actual_delivery);

        // Verify order item status
        $orderItem->refresh();
        $this->assertEquals('delivered', $orderItem->fulfillment_status);
    }

    public function test_cannot_deliver_pending_shipment(): void
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create([
            'sales_order_id' => $order->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Shipment must be shipped before marking as delivered');

        $this->service->markAsDelivered($shipment);
    }

    public function test_can_cancel_pending_shipment(): void
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->pending()->create([
            'sales_order_id' => $order->id,
        ]);

        $cancelled = $this->service->cancelShipment($shipment, 'Customer request');

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertArrayHasKey('cancellation_reason', $cancelled->metadata);
        $this->assertEquals('Customer request', $cancelled->metadata['cancellation_reason']);
    }

    public function test_cancelling_shipped_shipment_reverts_quantities(): void
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
            'quantity' => 10,
            'shipped_quantity' => 0,
        ]);

        $shipment = $this->service->createShipment($order, [
            $orderItem->id => 5,
        ]);

        $this->service->markAsShipped($shipment);

        $orderItem->refresh();
        $this->assertEquals(5, $orderItem->shipped_quantity);

        $this->service->cancelShipment($shipment->fresh());

        $orderItem->refresh();
        $this->assertEquals(0, $orderItem->shipped_quantity);
    }

    public function test_cannot_cancel_delivered_shipment(): void
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $shipment = Shipment::factory()->delivered()->create([
            'sales_order_id' => $order->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cancel a delivered shipment');

        $this->service->cancelShipment($shipment);
    }

    public function test_can_get_order_shipment_summary(): void
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
            'quantity' => 10,
            'shipped_quantity' => 0,
        ]);

        $shipment = $this->service->createShipment($order, [
            $orderItem->id => 5,
        ]);

        $this->service->markAsShipped($shipment);

        $summary = $this->service->getOrderShipmentSummary($order->fresh());

        $this->assertEquals($order->id, $summary['order_id']);
        $this->assertEquals(1, $summary['total_items']);
        $this->assertEquals(10, $summary['total_quantity']);
        $this->assertEquals(5, $summary['shipped_quantity']);
        $this->assertEquals(5, $summary['remaining_quantity']);
        $this->assertEquals(1, $summary['shipments_count']);
    }

    public function test_partial_shipment_updates_fulfillment_status(): void
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
            'quantity' => 10,
            'shipped_quantity' => 0,
        ]);

        // Ship 5 of 10
        $shipment1 = $this->service->createShipment($order, [
            $orderItem->id => 5,
        ]);
        $this->service->markAsShipped($shipment1);

        $orderItem->refresh();
        $this->assertEquals('partially_shipped', $orderItem->fulfillment_status);

        // Ship remaining 5
        $shipment2 = $this->service->createShipment($order->fresh(), [
            $orderItem->id => 5,
        ]);
        $this->service->markAsShipped($shipment2);

        $orderItem->refresh();
        $this->assertEquals('shipped', $orderItem->fulfillment_status);
    }

    public function test_order_status_updates_when_fully_shipped(): void
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
            'quantity' => 10,
            'shipped_quantity' => 0,
        ]);

        $shipment = $this->service->createShipment($order, [
            $orderItem->id => 10,
        ]);

        $this->service->markAsShipped($shipment);

        $order->refresh();
        $this->assertEquals('shipped', $order->status);
    }
}

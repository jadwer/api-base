<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Modules\Contacts\Models\Contact;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Events\SalesOrderDelivered;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\OrderStatusService;

/**
 * QA post-commit (C1): test de INVARIANTE, no de fachada.
 *
 * El bug que fija: entregar una orden por el endpoint de status del admin
 * (OrderStatusService::updateStatus('delivered')) quedaba sin salida de inventario,
 * sin COGS y sin factura tras neutralizar el Observer. Solo la entrega por remision
 * disparaba los efectos.
 *
 * Nota sobre Event::fake: aqui el DISPARO del evento ES el contrato bajo prueba (el
 * bug era que nunca se disparaba por este camino). Los efectos de inventario se
 * verifican REALES (el descuento ocurre en handleStatusChange, no en el listener).
 * El comportamiento del listener (crear ARInvoice) tiene sus propios tests.
 */
class SalesOrderDeliveryByStatusInvariantTest extends TestCase
{
    public function test_delivering_via_status_service_discounts_stock_and_fires_event(): void
    {
        Event::fake([SalesOrderDelivered::class]);

        $warehouse = Warehouse::factory()->create();
        $customer = Contact::factory()->customer()->create();

        $product = \Modules\Product\Models\Product::factory()->create(['cost' => 50]);
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'unit_cost' => 50,
        ]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'shipped',
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        app(OrderStatusService::class)->updateStatus($order, 'delivered');

        // Invariante 1: el stock fisico BAJA por la entrega.
        $this->assertEquals(6.0, (float) Stock::where('product_id', $product->id)->sum('quantity'));

        // Invariante 2: existe el movimiento de salida trazable a la orden.
        $exit = InventoryMovement::where('reference_type', 'sales_order')
            ->where('reference_id', $order->id)
            ->where('movement_type', 'exit')
            ->first();
        $this->assertNotNull($exit, 'La entrega por status debe crear el movimiento de salida');
        $this->assertEquals(4.0, (float) $exit->quantity);
        $this->assertTrue((bool) $exit->quality_checked, 'Exit del sistema exento de IV-009');

        // Invariante 3: el evento de facturacion se dispara por ESTE camino.
        Event::assertDispatched(SalesOrderDelivered::class, function ($event) use ($order) {
            return $event->salesOrder->id === $order->id;
        });
    }

    public function test_delivering_via_status_service_does_not_discount_when_order_has_remissions(): void
    {
        // La remision es duena del stock cuando existe: el camino por status no debe
        // duplicar la salida (guardia de C1 contra doble descuento).
        Event::fake([SalesOrderDelivered::class]);

        $warehouse = Warehouse::factory()->create();
        $customer = Contact::factory()->customer()->create();

        $product = \Modules\Product\Models\Product::factory()->create();
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'shipped',
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
        Remission::factory()->create([
            'sales_order_id' => $order->id,
            'status' => 'delivered',
        ]);

        app(OrderStatusService::class)->updateStatus($order, 'delivered');

        // El stock NO se toca por el camino de status: la remision es la duena.
        $this->assertEquals(10.0, (float) Stock::where('product_id', $product->id)->sum('quantity'));
        $this->assertEquals(
            0,
            InventoryMovement::where('reference_type', 'sales_order')->where('reference_id', $order->id)->count()
        );

        // El evento de facturacion SI se dispara igual (la factura no depende del stock).
        Event::assertDispatched(SalesOrderDelivered::class);
    }
}

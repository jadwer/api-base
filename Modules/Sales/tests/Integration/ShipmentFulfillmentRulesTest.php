<?php

namespace Modules\Sales\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\FolioSequence;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\OrderStatusService;
use Modules\Sales\Services\ShipmentService;
use Tests\TestCase;

/**
 * Surtido F3: el envio da salida con movimiento y costo de venta, coordinado
 * con la remision por lo pendiente de cada partida (puntos 4/5 del roadmap
 * docs/surtido-compras/ROADMAP_SURTIDO_COMPRAS.md).
 */
class ShipmentFulfillmentRulesTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;
    private Product $product;
    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            [
                'name' => now()->format('Y-m'),
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
            ]
        );
        FolioSequence::updateOrCreate(
            ['document_type' => 'remission'],
            ['prefix' => 'REM', 'include_year' => true, 'year_format' => 'y', 'separator' => '',
             'padding' => 6, 'current_sequence' => 0, 'is_active' => true]
        );

        $this->warehouse = Warehouse::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create(['cost' => 10]);
        $this->stock = Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'warehouse_location_id' => null,
            'quantity' => 100,
            'reserved_quantity' => 0,
            'unit_cost' => 10,
        ]);
    }

    private function order(float $quantity = 10): SalesOrder
    {
        $order = SalesOrder::factory()->draft()->create([
            'contact_id' => Contact::factory()->customer()->create(['status' => 'active'])->id,
            'metadata' => ['warehouse_id' => $this->warehouse->id],
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'unit_price' => 100,
            'discount' => 0,
        ]);

        return app(OrderStatusService::class)->updateStatus($order, 'confirmed')->fresh('items');
    }

    private function itemId(SalesOrder $order): int
    {
        return $order->items->first()->id;
    }

    private function service(): ShipmentService
    {
        return app(ShipmentService::class);
    }

    private function remit(SalesOrder $order, float $quantity): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')->postJson('/api/v1/remissions/from-order', [
            'sales_order_id' => $order->id,
            'items' => [['sales_order_item_id' => $this->itemId($order), 'quantity' => $quantity]],
        ]);
    }

    public function test_shipping_creates_an_exit_with_cost_and_releases_the_reservation(): void
    {
        $order = $this->order();
        $shipment = $this->service()->createShipment($order, [$this->itemId($order) => 4]);
        $this->service()->markAsShipped($shipment->fresh('items'));

        $exit = InventoryMovement::where('reference_type', 'shipment')->where('reference_id', $shipment->id)->first();
        $this->assertNotNull($exit, 'embarcar deja movimiento de salida');
        $this->assertEquals(4, (float) $exit->quantity);
        $this->assertEquals(40, (float) $exit->total_value, 'la salida lleva costo (COGS)');
        $this->assertEquals(96, (float) $this->stock->fresh()->quantity);
        $this->assertEquals(6, (float) $this->stock->fresh()->reserved_quantity);
    }

    public function test_shipment_and_remission_share_what_is_pending(): void
    {
        $order = $this->order();
        $this->remit($order, 6)->assertStatus(201);

        $this->expectExceptionMessage('Solo quedan 4 por surtir');
        $this->service()->createShipment($order->fresh('items'), [$this->itemId($order) => 5]);
    }

    public function test_open_shipment_reduces_what_a_remission_can_take(): void
    {
        $order = $this->order();
        $this->service()->createShipment($order, [$this->itemId($order) => 7]);

        $this->remit($order, 4)->assertStatus(422);
        $this->remit($order, 3)->assertStatus(201);
    }

    public function test_cancelling_a_shipped_shipment_restocks_once_and_reserves_again(): void
    {
        $order = $this->order();
        $shipment = $this->service()->createShipment($order, [$this->itemId($order) => 4]);
        $this->service()->markAsShipped($shipment->fresh('items'));
        $this->service()->cancelShipment($shipment->fresh('items'));

        $this->assertEquals(100, (float) $this->stock->fresh()->quantity);
        $this->assertEquals(10, (float) $this->stock->fresh()->reserved_quantity);

        // Cancelar la venta despues no repone esa salida otra vez.
        app(OrderStatusService::class)->updateStatus($order->fresh(), 'cancelled');
        $this->assertEquals(100, (float) $this->stock->fresh()->quantity);
        $this->assertEquals(0, (float) $this->stock->fresh()->reserved_quantity);
        $this->assertSame(1, InventoryMovement::where('reference_type', 'sales_cancel')->count());
    }

    public function test_mixed_remission_and_shipment_deliver_the_order_once(): void
    {
        $order = $this->order();

        $this->remit($order, 4)->assertStatus(201);
        Remission::query()->update(['status' => 'printed']);
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/remissions/' . Remission::first()->id . '/deliver')
            ->assertOk();
        $this->assertSame('processing', $order->fresh()->status);

        $shipment = $this->service()->createShipment($order->fresh('items'), [$this->itemId($order) => 6]);
        $this->service()->markAsShipped($shipment->fresh('items'));
        $this->service()->markAsDelivered($shipment->fresh('items'));

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertEquals(90, (float) $this->stock->fresh()->quantity);
        $this->assertEquals(0, (float) $this->stock->fresh()->reserved_quantity);
        $this->assertSame(0, InventoryMovement::where('reference_type', 'sales_order')->where('reference_id', $order->id)->count());
        $this->assertSame(1, ARInvoice::where('sales_order_id', $order->id)->count());
    }
}

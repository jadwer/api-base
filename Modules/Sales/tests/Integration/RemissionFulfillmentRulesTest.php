<?php

namespace Modules\Sales\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Contacts\Models\Contact;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\FolioSequence;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\OrderStatusService;
use Tests\TestCase;

/**
 * Surtido F2: remisiones y reservas (puntos 3, 6, 7 y 13 del roadmap
 * docs/surtido-compras/ROADMAP_SURTIDO_COMPRAS.md).
 */
class RemissionFulfillmentRulesTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;
    private Product $product;

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
    }

    private function stock(float $quantity, ?Warehouse $warehouse = null): Stock
    {
        return Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => ($warehouse ?? $this->warehouse)->id,
            'warehouse_location_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'unit_cost' => 10,
        ]);
    }

    /** Pedido con una partida; confirmado por la maquina de estados (aparta stock). */
    private function order(float $quantity, string $status = 'confirmed', ?Warehouse $warehouse = null): SalesOrder
    {
        $order = SalesOrder::factory()->draft()->create([
            'contact_id' => Contact::factory()->customer()->create(['status' => 'active'])->id,
            'metadata' => ['warehouse_id' => ($warehouse ?? $this->warehouse)->id],
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'unit_price' => 100,
            'discount' => 0,
        ]);

        if ($status !== 'draft') {
            $order = app(OrderStatusService::class)->updateStatus($order, $status);
        }

        return $order->fresh('items');
    }

    private function remit(SalesOrder $order, ?float $quantity = null): \Illuminate\Testing\TestResponse
    {
        $admin = $this->getAdminUser();

        if ($quantity === null) {
            return $this->actingAs($admin, 'sanctum')->postJson('/api/v1/remissions/from-order-full', [
                'sales_order_id' => $order->id,
            ]);
        }

        return $this->actingAs($admin, 'sanctum')->postJson('/api/v1/remissions/from-order', [
            'sales_order_id' => $order->id,
            'items' => [['sales_order_item_id' => $order->items->first()->id, 'quantity' => $quantity]],
        ]);
    }

    private function deliver(int $remissionId): \Illuminate\Testing\TestResponse
    {
        Remission::whereKey($remissionId)->update(['status' => 'printed']);

        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/remissions/{$remissionId}/deliver", ['received_by' => 'Almacen']);
    }

    // Punto 6 -----------------------------------------------------------------

    public function test_draft_or_cancelled_order_cannot_be_remitted(): void
    {
        $this->stock(50);

        $this->remit($this->order(5, 'draft'))->assertStatus(422)
            ->assertJsonFragment(['error' => 'El pedido debe estar confirmado, en proceso o enviado para remisionarse. Estado actual: draft.']);

        $cancelled = $this->order(5);
        app(OrderStatusService::class)->updateStatus($cancelled, 'cancelled');
        $this->remit($cancelled->fresh('items'))->assertStatus(422);

        $this->assertSame(0, Remission::count());
    }

    public function test_full_remission_only_takes_what_is_pending(): void
    {
        $this->stock(50);
        $order = $this->order(10);

        $this->remit($order, 4)->assertStatus(201);
        $this->remit($order)->assertStatus(201);

        $second = Remission::latest('id')->first();
        $this->assertEquals(6, (float) $second->items->first()->quantity);

        $this->remit($order)->assertStatus(422)
            ->assertJsonFragment(['error' => 'El pedido ya no tiene partidas pendientes por surtir.']);
        $this->remit($order, 1)->assertStatus(422);
    }

    public function test_shipped_quantity_counts_as_already_out(): void
    {
        $this->stock(50);
        $order = $this->order(10);
        $order->items->first()->update(['shipped_quantity' => 7]);

        $this->remit($order, 4)->assertStatus(422);
        $this->remit($order)->assertStatus(201);
        $this->assertEquals(3, (float) Remission::first()->items->first()->quantity);
    }

    // Punto 13 ----------------------------------------------------------------

    public function test_stock_is_validated_per_warehouse_not_summed(): void
    {
        $other = Warehouse::factory()->create(['is_active' => true]);
        $this->stock(6);
        $this->stock(6, $other);

        // Pedido sin apartar (pendiente -> se fuerza confirmado sin reserva).
        $order = $this->order(10, 'draft');
        $order->update(['status' => 'confirmed']);

        $response = $this->remit($order->fresh('items'));
        $response->assertStatus(422)->assertJsonPath('insufficient_items.0.available', 6);
    }

    public function test_own_reservation_counts_as_available(): void
    {
        $this->stock(10);
        $order = $this->order(10); // aparta las 10: disponible general 0

        $this->remit($order)->assertStatus(201);

        // Otro pedido no puede tomar lo apartado.
        $second = $this->order(1, 'draft');
        $second->update(['status' => 'confirmed']);
        $this->remit($second->fresh('items'))->assertStatus(422);
    }

    // Puntos 3 y 7 ------------------------------------------------------------

    public function test_partial_delivery_keeps_order_in_process_and_releases_only_what_left(): void
    {
        $stock = $this->stock(100);
        $order = $this->order(10);
        $this->order(20); // otro pedido aparta 20 del mismo stock
        $this->assertEquals(30, (float) $stock->fresh()->reserved_quantity);

        $this->remit($order, 4)->assertStatus(201);
        $this->deliver(Remission::first()->id)->assertOk();

        $order->refresh();
        $this->assertSame('processing', $order->status);
        $this->assertEquals(96, (float) $stock->fresh()->quantity);
        $this->assertEquals(26, (float) $stock->fresh()->reserved_quantity);
        $this->assertNull($order->ar_invoice_id);

        // Cancelar suelta solo las 6 pendientes; las 20 del otro pedido siguen.
        app(OrderStatusService::class)->updateStatus($order, 'cancelled');
        $this->assertEquals(20, (float) $stock->fresh()->reserved_quantity);
    }

    public function test_order_is_delivered_through_the_state_machine_when_fully_out(): void
    {
        $stock = $this->stock(100);
        $order = $this->order(10);

        $this->remit($order, 4)->assertStatus(201);
        $this->deliver(Remission::first()->id)->assertOk();
        $this->remit($order)->assertStatus(201);
        $this->deliver(Remission::latest('id')->first()->id)->assertOk();

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertEquals(90, (float) $stock->fresh()->quantity);
        $this->assertEquals(0, (float) $stock->fresh()->reserved_quantity);

        $steps = array_column($order->metadata['status_history'], 'to');
        $this->assertSame(['confirmed', 'processing', 'shipped', 'delivered'], $steps);
        $this->assertNotNull($order->ar_invoice_id, 'la entrega completa factura una sola vez');
    }

    public function test_open_remission_blocks_delivered_even_if_quantities_are_covered(): void
    {
        $this->stock(100);
        $order = $this->order(10);

        $this->remit($order, 4)->assertStatus(201);
        $this->remit($order)->assertStatus(201); // 6 restantes, sin entregar

        $this->deliver(Remission::first()->id)->assertOk();
        $this->assertSame('processing', $order->fresh()->status);
    }
}

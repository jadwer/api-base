<?php

namespace Modules\Sales\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Contacts\Models\Contact;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\FolioSequence;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\BackorderService;
use Modules\Sales\Services\OrderStatusService;
use Tests\TestCase;

/**
 * Surtido F4 (punto 12 del roadmap docs/surtido-compras/ROADMAP_SURTIDO_COMPRAS.md):
 * el faltante al confirmar queda en backorder y se surte por remision.
 */
class BackorderFulfillmentRulesTest extends TestCase
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
            'quantity' => 6,
            'reserved_quantity' => 0,
            'unit_cost' => 10,
        ]);
    }

    /** Pedido de 10 con solo 6 en existencia: aparta 6, backorder de 4. */
    private function shortOrder(): SalesOrder
    {
        $order = SalesOrder::factory()->draft()->create([
            'contact_id' => Contact::factory()->customer()->create(['status' => 'active'])->id,
            'metadata' => ['warehouse_id' => $this->warehouse->id],
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100,
            'discount' => 0,
        ]);

        return app(OrderStatusService::class)->updateStatus($order, 'confirmed')->fresh('items');
    }

    private function deliver(Remission $remission): void
    {
        $remission->update(['status' => 'printed']);
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/remissions/{$remission->id}/deliver")
            ->assertOk();
    }

    public function test_full_remission_leaves_out_what_is_backordered(): void
    {
        $order = $this->shortOrder();
        $this->assertEquals(4, (float) Backorder::first()->backorder_quantity);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/remissions/from-order-full', ['sales_order_id' => $order->id])
            ->assertStatus(201);

        $this->assertEquals(6, (float) Remission::first()->items->first()->quantity);
    }

    public function test_fulfilling_a_backorder_creates_its_remission_and_completes_the_order(): void
    {
        $order = $this->shortOrder();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/remissions/from-order-full', ['sales_order_id' => $order->id])
            ->assertStatus(201);
        $this->deliver(Remission::first());
        $this->assertSame('processing', $order->fresh()->status);

        // Llega la mercancia y se surte el backorder por su endpoint.
        $this->stock->update(['quantity' => (float) $this->stock->fresh()->quantity + 4]);
        $backorder = Backorder::first();
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/backorders/{$backorder->id}/fulfill", ['quantity' => 4])
            ->assertOk();

        $this->assertSame('fulfilled', $backorder->fresh()->status);
        $second = Remission::latest('id')->first();
        $this->assertEquals(4, (float) $second->items->first()->quantity);
        $this->assertStringContainsString($backorder->backorder_number, (string) $second->internal_notes);

        $this->deliver($second);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertEquals(0, (float) $this->stock->fresh()->quantity);
        $this->assertEquals(0, (float) $this->stock->fresh()->reserved_quantity);
    }

    public function test_backorder_is_not_fulfilled_without_stock(): void
    {
        $this->shortOrder();
        $backorder = Backorder::first();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/backorders/{$backorder->id}/fulfill", ['quantity' => 4])
            ->assertStatus(422);

        $this->assertEquals(0, (float) $backorder->fresh()->fulfilled_quantity);
        $this->assertSame(0, Remission::count());
    }

    public function test_auto_fulfillment_creates_one_remission_per_backorder(): void
    {
        $this->shortOrder();
        $this->stock->update(['quantity' => 10]);

        $result = app(BackorderService::class)->fulfillBackordersForProduct($this->product->id);

        $this->assertCount(1, $result);
        $this->assertSame('fulfilled', Backorder::first()->status);
        $this->assertSame(1, Remission::count());
        $this->assertEquals(4, (float) Remission::first()->items->first()->quantity);
    }

    public function test_order_created_as_confirmed_goes_through_the_state_machine(): void
    {
        $contact = Contact::factory()->customer()->create(['status' => 'active']);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/sales-orders/with-items', [
                'contact_id' => $contact->id,
                'order_date' => now()->toDateString(),
                'status' => 'confirmed',
                'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 100]],
            ])->assertStatus(201);

        $order = SalesOrder::find($response->json('data.id'));
        $this->assertSame('confirmed', $order->status);
        $this->assertEquals(6, (float) $this->stock->fresh()->reserved_quantity);
        $this->assertEquals(4, (float) Backorder::where('sales_order_id', $order->id)->value('backorder_quantity'));
    }
}

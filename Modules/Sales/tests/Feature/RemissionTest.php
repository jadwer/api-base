<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\RemissionItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\FolioSequence;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\Stock;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;

/**
 * SA-M011: Tests for Remission (Delivery Notes) functionality
 */
class RemissionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected SalesOrder $order;
    protected Warehouse $warehouse;
    protected Contact $contact;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contact = Contact::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();

        $this->order = SalesOrder::factory()->create([
            'contact_id' => $this->contact->id,
            'status' => 'confirmed',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
        ]);

        // Create stock for the product so remission stock validation passes
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        // Initialize folio sequence
        FolioSequence::updateOrCreate(
            ['document_type' => 'remission'],
            [
                'prefix' => 'REM',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '',
                'padding' => 6,
                'current_sequence' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * Helper method to create a remission with items
     */
    protected function createRemissionWithItems(array $remissionData = [], int $itemCount = 1): Remission
    {
        $remission = Remission::factory()->create(array_merge([
            'sales_order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
        ], $remissionData));

        for ($i = 0; $i < $itemCount; $i++) {
            RemissionItem::factory()->create([
                'remission_id' => $remission->id,
                'product_id' => $this->product->id,
                'sales_order_item_id' => $this->order->items->first()->id,
                'quantity' => 5,
            ]);
        }

        return $remission->fresh('items');
    }

    /** @test */
    public function it_can_create_remission_from_sales_order(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/remissions/from-order', [
                'sales_order_id' => $this->order->id,
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    [
                        'sales_order_item_id' => $this->order->items->first()->id,
                        'quantity' => 5,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $remission = Remission::first();
        $this->assertNotNull($remission);
        $this->assertEquals($this->order->id, $remission->sales_order_id);
        $this->assertEquals('draft', $remission->status);
        $this->assertCount(1, $remission->items);
        $this->assertEquals(5, $remission->items->first()->quantity);
    }

    /** @test */
    public function it_can_create_full_remission_from_order(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/remissions/from-order-full', [
                'sales_order_id' => $this->order->id,
                'warehouse_id' => $this->warehouse->id,
            ]);

        $response->assertStatus(201);

        $remission = Remission::first();
        $this->assertNotNull($remission);
        $this->assertEquals(10, $remission->items->first()->quantity);
    }

    /** @test */
    public function it_generates_folio_with_configurable_format(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/remissions/from-order-full', [
                'sales_order_id' => $this->order->id,
                'warehouse_id' => $this->warehouse->id,
            ]);

        $response->assertStatus(201);

        $remission = Remission::first();
        $year = now()->format('y');

        $this->assertEquals("REM{$year}000001", $remission->remission_number);
    }

    /** @test */
    public function it_can_mark_remission_as_printed(): void
    {
        $admin = $this->getAdminUser();

        // Create remission WITH items - required for canBePrinted()
        $remission = $this->createRemissionWithItems(['status' => 'draft']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/remissions/{$remission->id}/print");

        $response->assertStatus(200);

        $remission->refresh();
        $this->assertEquals('printed', $remission->status);
    }

    /** @test */
    public function it_can_mark_remission_as_delivered(): void
    {
        $admin = $this->getAdminUser();

        // Create remission with items in printed status
        $remission = $this->createRemissionWithItems(['status' => 'printed']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/remissions/{$remission->id}/deliver", [
                'received_by' => 'Juan Perez',
            ]);

        $response->assertStatus(200);

        $remission->refresh();
        $this->assertEquals('delivered', $remission->status);
        $this->assertNotNull($remission->delivered_at);
    }

    /** @test */
    public function it_can_cancel_remission(): void
    {
        $admin = $this->getAdminUser();

        $remission = $this->createRemissionWithItems(['status' => 'draft']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/remissions/{$remission->id}/cancel", [
                'reason' => 'Cliente cancelo el pedido',
            ]);

        $response->assertStatus(200);

        $remission->refresh();
        $this->assertEquals('cancelled', $remission->status);
    }

    /** @test */
    public function it_cannot_cancel_delivered_remission(): void
    {
        $admin = $this->getAdminUser();

        $remission = $this->createRemissionWithItems([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/remissions/{$remission->id}/cancel");

        $response->assertStatus(400);
    }

    /** @test */
    public function it_can_list_remissions_for_order(): void
    {
        $admin = $this->getAdminUser();

        // Create 3 remissions with items
        for ($i = 0; $i < 3; $i++) {
            $this->createRemissionWithItems();
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/sales-orders/{$this->order->id}/remissions");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_returns_remission_summary(): void
    {
        $admin = $this->getAdminUser();

        $this->createRemissionWithItems(['status' => 'draft']);
        $this->createRemissionWithItems(['status' => 'delivered']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/remissions/summary');

        $response->assertStatus(200);
        $response->assertJsonPath('data.total', 2);
    }
}

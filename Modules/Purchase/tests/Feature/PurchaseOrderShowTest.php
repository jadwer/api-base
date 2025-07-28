<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_show_purchase_order(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create([
            'order_date' => '2025-01-15',
            'status' => 'pending',
            'total_amount' => 1500.00,
            'notes' => 'Test purchase order notes'
        ]);

        $response = $this->jsonApi()->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'supplierId',
                    'orderDate',
                    'status', 
                    'totalAmount',
                    'notes',
                    'createdAt',
                    'updatedAt',
                ],
                'relationships' => [
                    'supplier' => [
                        'links'
                    ],
                    'purchaseOrderItems' => [
                        'links'
                    ],
                ],
                'links'
            ],
            'jsonapi',
            'links'
        ]);

        $response->assertJson([
            'data' => [
                'id' => (string) $purchaseOrder->id,
                'type' => 'purchase-orders',
                'attributes' => [
                    'supplierId' => $supplier->id,
                    'orderDate' => '2025-01-15T00:00:00.000000Z',
                    'status' => 'pending',
                    'totalAmount' => '1500.00',
                    'notes' => 'Test purchase order notes',
                ],
            ],
        ]);
    }

    public function test_admin_can_show_purchase_order_with_supplier_included(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();

        $response = $this->jsonApi()
            ->includePaths('supplier')
            ->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
        
        $included = collect($response->json('included'));
        $supplierData = $included->firstWhere('type', 'suppliers');
        
        $this->assertEquals($supplier->id, $supplierData['id']);
        $this->assertEquals('Test Supplier', $supplierData['attributes']['name']);
    }

    public function test_admin_can_show_purchase_order_with_items_included(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();

        $response = $this->jsonApi()
            ->includePaths('purchaseOrderItems')
            ->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        // Note: purchaseOrderItems will be empty array since no items created
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'purchaseOrderItems' => [
                        'data'
                    ],
                ],
            ],
        ]);
    }

    public function test_returns_404_for_nonexistent_purchase_order(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/purchase-orders/999999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_show_purchase_order(): void
    {
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();

        $response = $this->jsonApi()->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_show_purchase_order(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->jsonApi()->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(403);
    }
}

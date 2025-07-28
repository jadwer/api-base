<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    
    public function test_admin_can_view_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/suppliers/{$supplier->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'email',
                    'phone',
                    'address',
                    'rfc',
                    'isActive',
                    'createdAt',
                    'updatedAt',
                ],
            ],
            'jsonapi',
        ]);
        $this->assertEquals($supplier->id, $response->json('data.id'));
    }

    
    public function test_admin_can_view_supplier_with_purchase_orders(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        // Create purchase orders for this supplier if needed
        // PurchaseOrder::factory()->for($supplier)->count(2)->create();

        $response = $this->jsonApi()
            ->includePaths('purchaseOrders')
            ->get("/api/v1/suppliers/{$supplier->id}");

        $response->assertOk();
        $this->assertEquals($supplier->id, $response->json('data.id'));
    }

    
    public function test_admin_can_view_inactive_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create(['is_active' => false]);

        $response = $this->jsonApi()->get("/api/v1/suppliers/{$supplier->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }

    
    public function test_returns_404_for_nonexistent_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/suppliers/999999');

        $response->assertNotFound();
    }

    
    public function unauthorized_user_cannot_view_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/suppliers/{$supplier->id}");
        $response->assertStatus(401);
    }

    
    public function test_user_without_permission_cannot_view_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->jsonApi()->get("/api/v1/suppliers/{$supplier->id}");
        $response->assertStatus(403);
    }
}

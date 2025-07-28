<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_delete_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/suppliers/{$supplier->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_admin_can_delete_inactive_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create(['is_active' => false]);

        $response = $this->jsonApi()->delete("/api/v1/suppliers/{$supplier->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_cannot_delete_supplier_with_purchase_orders(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        // Create a purchase order for this supplier
        PurchaseOrder::factory()->for($supplier)->create();

        $response = $this->jsonApi()->delete("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_returns_404_for_nonexistent_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->delete('/api/v1/suppliers/999999');

        $response->assertNotFound();
    }

    public function test_cannot_delete_already_deleted_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $supplier->delete(); // Soft delete

        $response = $this->jsonApi()->delete("/api/v1/suppliers/{$supplier->id}");

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_delete_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->jsonApi()->delete("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(403);
    }
}

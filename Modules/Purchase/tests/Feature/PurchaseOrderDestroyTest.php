<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderDestroyTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPermissions(string $roleName = 'test_role', array $permissions = []): User
    {
        $user = User::factory()->create();

        $role = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => $roleName . '_' . uniqid(),
            'guard_name' => 'api'
        ]);

        foreach ($permissions as $permissionName) {
            $permission = \Modules\PermissionManager\Models\Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'api'
            ]);
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_delete_purchase_order(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['purchase-orders.destroy']);
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-orders')
            ->delete("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('purchase_orders', [
            'id' => $purchaseOrder->id,
        ]);
    }

    public function test_returns_404_for_nonexistent_purchase_order(): void
    {
        $admin = $this->createUserWithPermissions('admin', ['purchase-orders.destroy']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('purchase-orders')
            ->delete("/api/v1/purchase-orders/99999");

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_delete_purchase_order(): void
    {
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->pending()->create();

        $response = $this->jsonApi()
            ->expects('purchase-orders')
            ->delete("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_purchase_order(): void
    {
        $user = $this->createUserWithPermissions('user', []); // Sin permisos
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->pending()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('purchase-orders')
            ->delete("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
        ]);
    }
}

<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Product\Models\Product;
use Tests\TestCase;
use Modules\User\Models\User;

class StockDestroyTest extends TestCase
{
    protected function createUserWithPermissions(string $roleName, array $permissions): User
    {
        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName]);
        
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
            $role->givePermissionTo($permission);
        }
        
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_delete_stock()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.destroy']);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id
        ]);

        $this->assertDatabaseHas('stock', ['id' => $stock->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->delete("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(204);
        
        // Verificar que el stock fue eliminado de la base de datos
        $this->assertDatabaseMissing('stock', ['id' => $stock->id]);
    }

    public function test_delete_nonexistent_stock_returns_404()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.destroy']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->delete('/api/v1/stocks/99999');

        $response->assertStatus(404);
    }

    public function test_tech_cannot_delete_stock()
    {
        $tech = $this->createUserWithPermissions('tech', []);
        
        $stock = Stock::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->delete("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(403);
        
        // Verificar que el stock no fue eliminado
        $this->assertDatabaseHas('stock', ['id' => $stock->id]);
    }

    public function test_customer_cannot_delete_stock()
    {
        $customer = $this->createUserWithPermissions('customer', []);
        
        $stock = Stock::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->delete("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(403);
        
        // Verificar que el stock no fue eliminado
        $this->assertDatabaseHas('stock', ['id' => $stock->id]);
    }

    public function test_unauthenticated_user_cannot_delete_stock()
    {
        $stock = Stock::factory()->create();

        $response = $this->jsonApi()
            ->expects('stocks')
            ->delete("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(401);
        
        // Verificar que el stock no fue eliminado
        $this->assertDatabaseHas('stock', ['id' => $stock->id]);
    }
}

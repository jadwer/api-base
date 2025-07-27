<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'stocks.show']);
        Permission::firstOrCreate(['name' => 'stocks.view']);
        
        // Crear roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'tech']);
        Role::firstOrCreate(['name' => 'customer']);
    }

    private function createUserWithPermissions(string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        $roleModel = Role::findByName($role);
        
        if (!empty($permissions)) {
            $roleModel->givePermissionTo($permissions);
        }
        
        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_show_stock()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.show']);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'quantity' => 150.5000,
            'reserved_quantity' => 25.2500,
            'unit_cost' => 45.6700,
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(200);
        
        // Verificar estructura de respuesta
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'quantity',
                    'reservedQuantity',
                    'availableQuantity',
                    'minimumStock',
                    'unitCost',
                    'totalValue',
                    'status',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
        
        // Verificar datos específicos
        $stockData = $response->json('data');
        $this->assertEquals($stock->id, $stockData['id']);
        $this->assertEquals('stocks', $stockData['type']);
        $this->assertEquals('150.5000', $stockData['attributes']['quantity']);
        $this->assertEquals('25.2500', $stockData['attributes']['reservedQuantity']);
        $this->assertEquals('125.2500', $stockData['attributes']['availableQuantity']); // quantity - reserved_quantity
        $this->assertEquals('active', $stockData['attributes']['status']);
    }

    public function test_admin_can_show_stock_with_relationships()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.show']);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get("/api/v1/stocks/{$stock->id}?include=product,warehouse,location");

        $response->assertStatus(200);
        
        // Verificar que las relaciones están incluidas
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'product',
                    'warehouse',
                    'location'
                ]
            ],
            'included'
        ]);
        
        // Verificar que hay 3 recursos incluidos (product, warehouse, location)
        $included = $response->json('included');
        $this->assertCount(3, $included);
        
        // Verificar tipos de relaciones
        $includedTypes = collect($included)->pluck('type')->toArray();
        $this->assertContains('products', $includedTypes);
        $this->assertContains('warehouses', $includedTypes);
        $this->assertContains('warehouse-locations', $includedTypes);
    }

    public function test_tech_can_show_stock()
    {
        $tech = $this->createUserWithPermissions('tech', ['stocks.show']);
        
        $stock = Stock::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(200);
        
        $stockData = $response->json('data');
        $this->assertEquals($stock->id, $stockData['id']);
    }

    public function test_customer_cannot_show_stock()
    {
        $customer = $this->createUserWithPermissions('customer', []);
        
        $stock = Stock::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_show_stock()
    {
        $stock = Stock::factory()->create();

        $response = $this->jsonApi()
            ->expects('stocks')
            ->get("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_stock_returns_404()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.show']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get("/api/v1/stocks/99999");

        $response->assertStatus(404);
    }
}

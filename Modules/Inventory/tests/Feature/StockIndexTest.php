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

class StockIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'stocks.index']);
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

    public function test_admin_can_list_stocks()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.index']);
        
        // Crear stocks de prueba
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $stock1 = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'quantity' => 100.5000,
            'status' => 'active'
        ]);
        
        $stock2 = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'quantity' => 50.2500,
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get('/api/v1/stocks');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        
        // Verificar estructura de respuesta
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'quantity',
                        'reservedQuantity',
                        'availableQuantity',
                        'minimumStock',
                        'unitCost',
                        'totalValue',
                        'status'
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_stocks_by_quantity()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.index']);
        
        $warehouse = Warehouse::factory()->create();
        
        // Crear stocks con diferentes cantidades
        $stock1 = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'quantity' => 100.0000
        ]);
        $stock2 = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'quantity' => 50.0000
        ]);
        $stock3 = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'quantity' => 200.0000
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get('/api/v1/stocks?sort=-quantity');

        $response->assertStatus(200);
        
        $quantities = collect($response->json('data'))
            ->pluck('attributes.quantity')
            ->map(fn($q) => (float) $q);
            
        $this->assertEquals([200.0, 100.0, 50.0], $quantities->toArray());
    }

    public function test_admin_can_filter_stocks_by_status()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.index']);
        
        $warehouse = Warehouse::factory()->create();
        
        // Crear stocks con diferentes estados
        $activeStock = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'active'
        ]);
        $inactiveStock = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'inactive'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get('/api/v1/stocks?filter[status]=active');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        
        $stockData = $response->json('data.0');
        $this->assertEquals($activeStock->id, $stockData['id']);
        $this->assertEquals('active', $stockData['attributes']['status']);
    }

    public function test_admin_can_filter_stocks_by_warehouse()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.index']);
        
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        
        // Crear stocks en diferentes bodegas
        $stock1 = Stock::factory()->create(['warehouse_id' => $warehouse1->id]);
        $stock2 = Stock::factory()->create(['warehouse_id' => $warehouse2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get("/api/v1/stocks?filter[warehouse_id]={$warehouse1->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        
        $stockData = $response->json('data.0');
        $this->assertEquals($stock1->id, $stockData['id']);
    }

    public function test_tech_can_list_stocks()
    {
        $tech = $this->createUserWithPermissions('tech', ['stocks.index']);
        
        $warehouse = Warehouse::factory()->create();
        Stock::factory()->create(['warehouse_id' => $warehouse->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get('/api/v1/stocks');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_customer_cannot_list_stocks()
    {
        $customer = $this->createUserWithPermissions('customer', []);
        
        Stock::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->get('/api/v1/stocks');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_stocks()
    {
        Stock::factory()->create();

        $response = $this->jsonApi()
            ->expects('stocks')
            ->get('/api/v1/stocks');

        $response->assertStatus(401);
    }
}

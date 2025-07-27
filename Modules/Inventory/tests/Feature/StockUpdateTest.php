<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Product\Models\Product;
use Tests\TestCase;
use Modules\User\Models\User;

class StockUpdateTest extends TestCase
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

    public function test_admin_can_update_stock()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.update']);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'quantity' => 100.0000,
            'unit_cost' => 25.0000,
            'status' => 'active'
        ]);

        $updatedData = [
            'type' => 'stocks',
            'id' => (string) $stock->id,
            'attributes' => [
                'quantity' => 150.7500,
                'reservedQuantity' => 25.5000,
                'minimumStock' => 20.0000,
                'maximumStock' => 800.0000,
                'reorderPoint' => 30.0000,
                'unitCost' => 30.2500,
                'status' => 'quarantine',
                'batchInfo' => [
                    'batch_number' => 'BATCH987654',
                    'expiration_date' => '2027-06-30'
                ],
                'metadata' => [
                    'supplier' => 'Updated Supplier',
                    'quality_grade' => 'B',
                    'notes' => 'Updated notes'
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($updatedData)
            ->patch("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(200);
        
        // Verificar que el stock fue actualizado en la base de datos
        $this->assertDatabaseHas('stock', [
            'id' => $stock->id,
            'quantity' => 150.7500,
            'reserved_quantity' => 25.5000,
            'minimum_stock' => 20.0000,
            'maximum_stock' => 800.0000,
            'reorder_point' => 30.0000,
            'unit_cost' => 30.2500,
            'status' => 'quarantine'
        ]);

        // Verificar estructura de respuesta JSON:API
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'quantity',
                    'reservedQuantity',
                    'unitCost',
                    'status',
                    'batchInfo',
                    'metadata'
                ]
            ]
        ]);
        
        $response->assertJson([
            'data' => [
                'type' => 'stocks',
                'id' => (string) $stock->id,
                'attributes' => [
                    'quantity' => 150.7500,
                    'status' => 'quarantine'
                ]
            ]
        ]);
    }

    public function test_stock_update_validates_required_fields()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.update']);
        
        $stock = Stock::factory()->create();

        $updatedData = [
            'type' => 'stocks',
            'id' => (string) $stock->id,
            'attributes' => [
                'quantity' => null,
                'unitCost' => null,
                'status' => null
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($updatedData)
            ->patch("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(422);
        
        // Verificar errores de validación específicos
        $this->assertJsonApiValidationErrors([
            '/data/attributes/quantity',
            '/data/attributes/unitCost',
            '/data/attributes/status'
        ], $response);
    }

    public function test_stock_update_validates_negative_values()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.update']);
        
        $stock = Stock::factory()->create();

        $updatedData = [
            'type' => 'stocks',
            'id' => (string) $stock->id,
            'attributes' => [
                'quantity' => -10.0000,
                'reservedQuantity' => -5.0000,
                'minimumStock' => -1.0000,
                'unitCost' => -25.7500,
                'status' => 'active'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($updatedData)
            ->patch("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(422);
        
        // Verificar errores de validación para valores negativos
        $this->assertJsonApiValidationErrors([
            '/data/attributes/quantity',
            '/data/attributes/reservedQuantity',
            '/data/attributes/minimumStock',
            '/data/attributes/unitCost'
        ], $response);
    }

    public function test_stock_update_validates_status_enum()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.update']);
        
        $stock = Stock::factory()->create();

        $updatedData = [
            'type' => 'stocks',
            'id' => (string) $stock->id,
            'attributes' => [
                'quantity' => 100.0000,
                'unitCost' => 25.0000,
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($updatedData)
            ->patch("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(422);
        
        // Verificar error de validación para el enum de status
        $this->assertJsonApiValidationErrors([
            '/data/attributes/status'
        ], $response);
    }

    public function test_update_nonexistent_stock_returns_404()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.update']);

        $updatedData = [
            'type' => 'stocks',
            'id' => '99999',
            'attributes' => [
                'quantity' => 100.0000,
                'unitCost' => 25.0000,
                'status' => 'active'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($updatedData)
            ->patch('/api/v1/stocks/99999');

        $response->assertStatus(404);
    }

    public function test_tech_cannot_update_stock()
    {
        $tech = $this->createUserWithPermissions('tech', []);
        
        $stock = Stock::factory()->create();

        $updatedData = [
            'type' => 'stocks',
            'id' => (string) $stock->id,
            'attributes' => [
                'quantity' => 200.0000,
                'unitCost' => 30.0000,
                'status' => 'inactive'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($updatedData)
            ->patch("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(403);
    }

    public function test_customer_cannot_update_stock()
    {
        $customer = $this->createUserWithPermissions('customer', []);
        
        $stock = Stock::factory()->create();

        $updatedData = [
            'type' => 'stocks',
            'id' => (string) $stock->id,
            'attributes' => [
                'quantity' => 200.0000,
                'unitCost' => 30.0000,
                'status' => 'inactive'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($updatedData)
            ->patch("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_stock()
    {
        $stock = Stock::factory()->create();

        $updatedData = [
            'type' => 'stocks',
            'id' => (string) $stock->id,
            'attributes' => [
                'quantity' => 200.0000,
                'unitCost' => 30.0000,
                'status' => 'inactive'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('stocks')
            ->withData($updatedData)
            ->patch("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(401);
    }
}

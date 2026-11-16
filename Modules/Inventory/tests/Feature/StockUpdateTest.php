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
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
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
                'minimumStock' => 20.0000,
                'maximumStock' => 800.0000,
                'reorderPoint' => 30.0000,
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
            // Existencia y costo no cambian por la API: los mueven los movimientos
            'quantity' => 100.0000,
            'minimum_stock' => 20.0000,
            'maximum_stock' => 800.0000,
            'reorder_point' => 30.0000,
            'unit_cost' => 25.0000,
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
                    'quantity' => 100.0,
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
                'minimumStock' => -1.0000,
                'maximumStock' => -2.0000,
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
            '/data/attributes/minimumStock',
            '/data/attributes/maximumStock'
        ], $response);
    }

    public function test_la_existencia_y_el_costo_no_se_editan_a_mano()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.update']);
        $stock = Stock::factory()->create(['quantity' => 40, 'unit_cost' => 12]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData(['type' => 'stocks', 'id' => (string) $stock->id, 'attributes' => ['quantity' => 999, 'reservedQuantity' => 1, 'unitCost' => 1]])
            ->patch("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(422)
            ->assertJsonFragment(['detail' => 'La existencia no se captura a mano: registra una entrada o un ajuste en Movimientos.'])
            ->assertJsonFragment(['detail' => 'Lo reservado lo calculan los pedidos; no se captura a mano.'])
            ->assertJsonFragment(['detail' => 'El costo sale de las entradas registradas; no se captura a mano.']);
        $this->assertDatabaseHas('stock', ['id' => $stock->id, 'quantity' => 40, 'unit_cost' => 12]);
    }

    public function test_stock_update_validates_status_enum()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.update']);
        
        $stock = Stock::factory()->create();

        $updatedData = [
            'type' => 'stocks',
            'id' => (string) $stock->id,
            'attributes' => [
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

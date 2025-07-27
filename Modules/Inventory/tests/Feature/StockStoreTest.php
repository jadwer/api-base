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

class StockStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'stocks.store']);
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

    public function test_admin_can_create_stock()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.store']);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        $stockData = [
            'type' => 'stocks',
            'attributes' => [
                'quantity' => 100.5000,
                'reservedQuantity' => 15.2500,
                'minimumStock' => 10.0000,
                'maximumStock' => 500.0000,
                'reorderPoint' => 20.0000,
                'unitCost' => 25.7500,
                'status' => 'active',
                'batchInfo' => [
                    'batch_number' => 'BAT123456',
                    'expiration_date' => '2026-12-31'
                ],
                'metadata' => [
                    'supplier' => 'Test Supplier',
                    'quality_grade' => 'A'
                ]
            ],
            'relationships' => [
                'product' => [
                    'data' => ['type' => 'products', 'id' => (string) $product->id]
                ],
                'warehouse' => [
                    'data' => ['type' => 'warehouses', 'id' => (string) $warehouse->id]
                ],
                'location' => [
                    'data' => ['type' => 'warehouse-locations', 'id' => (string) $location->id]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($stockData)
            ->post('/api/v1/stocks');

        $response->assertStatus(201);
        
        // Verificar que el stock fue creado en la base de datos
        $this->assertDatabaseHas('stock', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'quantity' => 100.5000,
            'reserved_quantity' => 15.2500,
            'minimum_stock' => 10.0000,
            'unit_cost' => 25.7500,
            'status' => 'active'
        ]);
        
        // Verificar estructura de respuesta
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'quantity',
                    'reservedQuantity',
                    'availableQuantity',
                    'unitCost',
                    'totalValue',
                    'status'
                ]
            ]
        ]);
    }

    public function test_stock_creation_validates_required_fields()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.store']);

        $stockData = [
            'type' => 'stocks',
            'attributes' => [
                // Faltan campos requeridos: quantity, unitCost, status
                'minimumStock' => 10.0000
            ]
            // Faltan relaciones requeridas: product, warehouse
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($stockData)
            ->post('/api/v1/stocks');

        $response->assertStatus(422);
        
        // Verificar errores de validación
        $this->assertJsonApiValidationErrors([
            '/data/attributes/quantity',
            '/data/attributes/unitCost',
            '/data/attributes/status',
            '/data/relationships/product',
            '/data/relationships/warehouse'
        ], $response);
    }

    public function test_stock_creation_validates_negative_values()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.store']);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stockData = [
            'type' => 'stocks',
            'attributes' => [
                'quantity' => -10.0000,
                'reservedQuantity' => -5.0000,
                'minimumStock' => -1.0000,
                'unitCost' => -25.7500,
                'status' => 'active'
            ],
            'relationships' => [
                'product' => [
                    'data' => ['type' => 'products', 'id' => (string) $product->id]
                ],
                'warehouse' => [
                    'data' => ['type' => 'warehouses', 'id' => (string) $warehouse->id]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($stockData)
            ->post('/api/v1/stocks');

        $response->assertStatus(422);
        
        // Verificar errores de validación para valores negativos
        $this->assertJsonApiValidationErrors([
            '/data/attributes/quantity',
            '/data/attributes/reservedQuantity',
            '/data/attributes/minimumStock',
            '/data/attributes/unitCost'
        ], $response);
    }

    public function test_stock_creation_validates_status_enum()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.store']);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stockData = [
            'type' => 'stocks',
            'attributes' => [
                'quantity' => 100.0000,
                'unitCost' => 25.7500,
                'status' => 'invalid_status'
            ],
            'relationships' => [
                'product' => [
                    'data' => ['type' => 'products', 'id' => (string) $product->id]
                ],
                'warehouse' => [
                    'data' => ['type' => 'warehouses', 'id' => (string) $warehouse->id]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($stockData)
            ->post('/api/v1/stocks');

        $response->assertStatus(422);
        
        $this->assertJsonApiValidationErrors([
            '/data/attributes/status'
        ], $response);
    }

    public function test_stock_creation_validates_unique_constraint()
    {
        $admin = $this->createUserWithPermissions('admin', ['stocks.store']);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);

        // Crear stock existente
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id
        ]);

        // Intentar crear un duplicado
        $stockData = [
            'type' => 'stocks',
            'attributes' => [
                'quantity' => 50.0000,
                'unitCost' => 30.0000,
                'status' => 'active'
            ],
            'relationships' => [
                'product' => [
                    'data' => ['type' => 'products', 'id' => (string) $product->id]
                ],
                'warehouse' => [
                    'data' => ['type' => 'warehouses', 'id' => (string) $warehouse->id]
                ],
                'location' => [
                    'data' => ['type' => 'warehouse-locations', 'id' => (string) $location->id]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($stockData)
            ->post('/api/v1/stocks');

        $response->assertStatus(422);
        
        // Verificar que el error es de validación
        $this->assertJsonApiValidationErrors([
            '/data/relationships/product'
        ], $response); 
    }

    public function test_tech_cannot_create_stock()
    {
        $tech = $this->createUserWithPermissions('tech', []);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stockData = [
            'type' => 'stocks',
            'attributes' => [
                'quantity' => 100.0000,
                'unitCost' => 25.0000,
                'status' => 'active'
            ],
            'relationships' => [
                'product' => [
                    'data' => ['type' => 'products', 'id' => (string) $product->id]
                ],
                'warehouse' => [
                    'data' => ['type' => 'warehouses', 'id' => (string) $warehouse->id]
                ]
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($stockData)
            ->post('/api/v1/stocks');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_stock()
    {
        $customer = $this->createUserWithPermissions('customer', []);
        
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stockData = [
            'type' => 'stocks',
            'attributes' => [
                'quantity' => 100.0000,
                'unitCost' => 25.0000,
                'status' => 'active'
            ],
            'relationships' => [
                'product' => [
                    'data' => ['type' => 'products', 'id' => (string) $product->id]
                ],
                'warehouse' => [
                    'data' => ['type' => 'warehouses', 'id' => (string) $warehouse->id]
                ]
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('stocks')
            ->withData($stockData)
            ->post('/api/v1/stocks');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_stock()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $stockData = [
            'type' => 'stocks',
            'attributes' => [
                'quantity' => 100.0000,
                'unitCost' => 25.0000,
                'status' => 'active'
            ],
            'relationships' => [
                'product' => [
                    'data' => ['type' => 'products', 'id' => (string) $product->id]
                ],
                'warehouse' => [
                    'data' => ['type' => 'warehouses', 'id' => (string) $warehouse->id]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('stocks')
            ->withData($stockData)
            ->post('/api/v1/stocks');

        $response->assertStatus(401);
    }
}

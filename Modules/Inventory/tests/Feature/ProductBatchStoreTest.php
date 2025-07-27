<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductBatchStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'product-batches.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'product-batches.view', 'guard_name' => 'api']);
        
        // Crear roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'tech', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
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

    public function test_admin_can_create_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.store']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $location = WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id]);
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'attributes' => [
                    'batchNumber' => 'BATCH001',
                    'lotNumber' => 'LOT001',
                    'manufacturingDate' => '2025-01-01',
                    'expirationDate' => '2025-12-31',
                    'initialQuantity' => 100.0,
                    'currentQuantity' => 100.0,
                    'reservedQuantity' => 0.0,
                    'unitCost' => 25.5,
                    'status' => 'active',
                    'supplierName' => 'Test Supplier',
                    'supplierBatch' => 'SUP001',
                    'qualityNotes' => 'Good quality batch',
                    'testResults' => [
                        'ph' => 7.2,
                        'quality_grade' => 'A'
                    ],
                    'certifications' => [
                        'ISO9001' => true,
                        'HACCP' => true
                    ],
                    'metadata' => [
                        'inspector' => 'John Doe'
                    ]
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => (string) $product->id
                        ]
                    ],
                    'warehouse' => [
                        'data' => [
                            'type' => 'warehouses',
                            'id' => (string) $warehouse->id
                        ]
                    ],
                    'warehouseLocation' => [
                        'data' => [
                            'type' => 'warehouse-locations',
                            'id' => (string) $location->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->post('/api/v1/product-batches');

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'batchNumber',
                    'lotNumber',
                    'manufacturingDate',
                    'expirationDate',
                    'initialQuantity',
                    'currentQuantity',
                    'unitCost',
                    'status'
                ]
            ]
        ]);
        
        $this->assertEquals('BATCH001', $response->json('data.attributes.batchNumber'));
        $this->assertEquals('active', $response->json('data.attributes.status'));
        
        // Verificar que se creó en la base de datos
        $this->assertDatabaseHas('product_batches', [
            'batch_number' => 'BATCH001',
            'lot_number' => 'LOT001',
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'status' => 'active'
        ]);
    }

    public function test_admin_can_create_minimal_product_batch()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.store']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'attributes' => [
                    'batchNumber' => 'MINIMAL001',
                    'initialQuantity' => 50.0,
                    'currentQuantity' => 50.0,
                    'unitCost' => 10.0,
                    'status' => 'active'
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => (string) $product->id
                        ]
                    ],
                    'warehouse' => [
                        'data' => [
                            'type' => 'warehouses',
                            'id' => (string) $warehouse->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->post('/api/v1/product-batches');

        $response->assertStatus(201);
        $this->assertEquals('MINIMAL001', $response->json('data.attributes.batchNumber'));
        $this->assertEquals('active', $response->json('data.attributes.status')); // Default value
    }

    public function test_store_validates_required_fields()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.store']);
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'attributes' => (object) [
                    // Missing required fields
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->post('/api/v1/product-batches');

        $response->assertStatus(422);
        
        // Verificar errores de validación para campos requeridos
        $this->assertJsonApiValidationErrors([
            '/data/attributes/batchNumber',
            '/data/attributes/initialQuantity',
            '/data/attributes/currentQuantity'
        ], $response);
    }

    public function test_store_validates_unique_batch_number()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.store']);
        
        // Crear un batch existente
        $existingBatch = ProductBatch::factory()->create([
            'batch_number' => 'DUPLICATE001'
        ]);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'attributes' => [
                    'batchNumber' => 'DUPLICATE001', // Número duplicado
                    'initialQuantity' => 50.0000,
                    'currentQuantity' => 50.0000,
                    'unitCost' => 10.0000,
                    'status' => 'active'
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => (string) $product->id
                        ]
                    ],
                    'warehouse' => [
                        'data' => [
                            'type' => 'warehouses',
                            'id' => (string) $warehouse->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->post('/api/v1/product-batches');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/batchNumber'
        ], $response);
    }

    public function test_store_validates_date_constraints()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.store']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'attributes' => [
                    'batchNumber' => 'DATE001',
                    'manufacturingDate' => '2025-12-31',
                    'expirationDate' => '2025-01-01', // Antes de la fecha de fabricación
                    'initialQuantity' => 50.0000,
                    'currentQuantity' => 50.0000,
                    'unitCost' => 10.0000,
                    'status' => 'active'
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => (string) $product->id
                        ]
                    ],
                    'warehouse' => [
                        'data' => [
                            'type' => 'warehouses',
                            'id' => (string) $warehouse->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->post('/api/v1/product-batches');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/expirationDate'
        ], $response);
    }

    public function test_store_validates_quantity_constraints()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-batches.store']);
        
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'attributes' => [
                    'batchNumber' => 'QTY001',
                    'initialQuantity' => 50.0000,
                    'currentQuantity' => 100.0000, // Mayor que initial_quantity
                    'unitCost' => 10.0000,
                    'status' => 'active'
                ],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => (string) $product->id
                        ]
                    ],
                    'warehouse' => [
                        'data' => [
                            'type' => 'warehouses',
                            'id' => (string) $warehouse->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->post('/api/v1/product-batches');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/currentQuantity'
        ], $response);
    }

    public function test_unauthorized_user_cannot_create_product_batch()
    {
        $data = [
            'data' => [
                'type' => 'product-batches',
                'attributes' => [
                    'batchNumber' => 'UNAUTH001'
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->post('/api/v1/product-batches');
            
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_create_product_batch()
    {
        $user = $this->createUserWithPermissions('customer', []);
        
        $data = [
            'data' => [
                'type' => 'product-batches',
                'attributes' => [
                    'batchNumber' => 'NOPERM001'
                ]
            ]
        ];
        
        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('product-batches')
            ->withData($data['data'])
            ->post('/api/v1/product-batches');
            
        $response->assertStatus(403);
    }
}

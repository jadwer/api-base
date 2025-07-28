<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\Supplier;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderItemStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar solo los seeders específicos que necesitamos
        $this->seed(\Modules\PermissionManager\Database\Seeders\RoleSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\AssignPermissionsSeeder::class);
        $this->seed(\Modules\Purchase\Database\Seeders\PurchaseOrderItemPermissionSeeder::class);

        // Crear manualmente el usuario admin para evitar conflictos
        $this->createAdminUser();
    }

    private function createAdminUser(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador General',
                'password' => 'secureadmin',
                'status' => 'active',
            ]
        );

        // Asignar rol admin si no lo tiene (solo para guard api)
        if (!$admin->hasRole('admin', 'api')) {
            $admin->assignRole('admin');
        }

        return $admin;
    }

    private function createUserWithPermissions(string $roleName = 'test_role', array $permissions = []): User
    {
        $user = User::factory()->create();
        
        // Crear rol y asignar permisos
        $role = \Spatie\Permission\Models\Role::create(['name' => $roleName, 'guard_name' => 'api']);
        
        foreach ($permissions as $permission) {
            $permissionModel = \Spatie\Permission\Models\Permission::where('name', $permission)
                ->where('guard_name', 'api')
                ->first();
            if ($permissionModel) {
                $role->givePermissionTo($permissionModel);
            }
        }
        
        $user->assignRole($role);
        
        return $user;
    }

    private function createPurchaseOrderAndProduct(): array
    {
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $product = Product::factory()->create();

        return [$purchaseOrder, $product];
    }

    public function test_admin_user_can_create_purchase_order_item(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        [$purchaseOrder, $product] = $this->createPurchaseOrderAndProduct();

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                'quantity' => 10,
                'unitPrice' => 25.50,
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-orders',
                        'id' => (string) $purchaseOrder->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-order-items');

        $response->assertCreated();

        $response->assertJson([
            'data' => [
                'type' => 'purchase-order-items',
                'attributes' => [
                    'quantity' => 10,
                    'unitPrice' => '25.50',
                ],
            ],
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => '25.50',
        ]);
    }

    public function test_admin_can_create_purchase_order_item(): void
    {
        $admin = $this->createUserWithPermissions('test', ['purchase-order-items.store']);
        $this->actingAs($admin, 'sanctum');
        
        [$purchaseOrder, $product] = $this->createPurchaseOrderAndProduct();

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                'quantity' => 5,
                'unitPrice' => 15.75,
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-orders',
                        'id' => (string) $purchaseOrder->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-order-items');

        $response->assertCreated();
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => '15.75',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                // Missing required fields - only include empty values
                'quantity' => null,
                'unitPrice' => null,
            ],
            // Missing relationships completely
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-order-items');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/quantity',
            '/data/attributes/unitPrice',
        ], $response);
    }

    public function test_store_validates_purchase_order_relationship(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        [$purchaseOrder, $product] = $this->createPurchaseOrderAndProduct();

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                'quantity' => 10,
                'unitPrice' => 25.50,
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-orders',
                        'id' => '999999', // Non-existent ID
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-order-items');

        $response->assertStatus(404); // Should be 404 when resource doesn't exist
        $this->assertJsonApiValidationErrors([
            '/data/relationships/purchaseOrder',
        ], $response);
    }

    public function test_store_validates_product_relationship(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        [$purchaseOrder, $product] = $this->createPurchaseOrderAndProduct();

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                'quantity' => 10,
                'unitPrice' => 25.50,
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-orders',
                        'id' => (string) $purchaseOrder->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => '999999', // Non-existent ID
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-order-items');

        $response->assertStatus(404); // Should be 404 when resource doesn't exist
        $this->assertJsonApiValidationErrors([
            '/data/relationships/product',
        ], $response);
    }

    public function test_store_validates_positive_quantity(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        [$purchaseOrder, $product] = $this->createPurchaseOrderAndProduct();

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                'quantity' => -5, // Invalid negative quantity
                'unitPrice' => 25.50,
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-orders',
                        'id' => (string) $purchaseOrder->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-order-items');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/quantity',
        ], $response);
    }

    public function test_store_validates_positive_unit_price(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        [$purchaseOrder, $product] = $this->createPurchaseOrderAndProduct();

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                'quantity' => 10,
                'unitPrice' => -15.50, // Invalid negative price
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-orders',
                        'id' => (string) $purchaseOrder->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-order-items');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/unitPrice',
        ], $response);
    }

    public function test_guest_cannot_create_purchase_order_item(): void
    {
        [$purchaseOrder, $product] = $this->createPurchaseOrderAndProduct();

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                'quantity' => 10,
                'unitPrice' => 25.50,
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-orders',
                        'id' => (string) $purchaseOrder->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-order-items');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_create_purchase_order_item(): void
    {
        $user = $this->createUserWithPermissions('test', []); // No permissions
        [$purchaseOrder, $product] = $this->createPurchaseOrderAndProduct();

        $data = [
            'type' => 'purchase-order-items',
            'attributes' => [
                'quantity' => 10,
                'unitPrice' => 25.50,
            ],
            'relationships' => [
                'purchaseOrder' => [
                    'data' => [
                        'type' => 'purchase-orders',
                        'id' => (string) $purchaseOrder->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/purchase-order-items');

        $response->assertStatus(403);
    }
}

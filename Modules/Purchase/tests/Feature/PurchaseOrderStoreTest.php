<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_create_purchase_order(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                'orderDate' => '2025-01-15',
                'status' => 'pending',
                'totalAmount' => 2500.50,
                'notes' => 'New purchase order for office supplies',
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => (string) $supplier->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'orderDate',
                    'status',
                    'totalAmount',
                    'notes',
                    'createdAt',
                    'updatedAt',
                ],
            ],
            'jsonapi',
        ]);

        $response->assertJson([
            'data' => [
                'type' => 'purchase-orders',
                'attributes' => [
                    'orderDate' => '2025-01-15T00:00:00.000000Z',
                    'status' => 'pending',
                    'totalAmount' => '2500.50',
                    'notes' => 'New purchase order for office supplies',
                ],
            ],
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'order_date' => '2025-01-15',
            'status' => 'pending',
            'total_amount' => '2500.50',
            'notes' => 'New purchase order for office supplies',
        ]);
    }

    public function test_admin_can_create_purchase_order_with_minimal_data(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                'orderDate' => '2025-01-20',
                'status' => 'pending',
                'totalAmount' => 1000.00,
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => (string) $supplier->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertCreated();
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'order_date' => '2025-01-20',
            'status' => 'pending',
            'total_amount' => '1000.00',
            'notes' => null,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                // Missing required fields - only providing optional ones
                'notes' => 'Some notes',
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/orderDate',
            '/data/attributes/status', 
            '/data/attributes/totalAmount',
            '/data/relationships/supplier',
        ], $response);
    }

    public function test_store_validates_supplier_relationship(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                'orderDate' => '2025-01-15',
                'status' => 'pending',
                'totalAmount' => 1500.00,
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => '999999', // Non-existent supplier
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertStatus(404);
        $response->assertJsonStructure([
            'errors' => [
                [
                    'title',
                    'detail',
                    'status'
                ]
            ]
        ]);
    }

    public function test_store_validates_status_enum(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                'orderDate' => '2025-01-15',
                'status' => 'invalid_status', // Invalid status
                'totalAmount' => 1500.00,
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => (string) $supplier->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/status',
        ], $response);
    }

    public function test_store_validates_total_amount_positive(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                'orderDate' => '2025-01-15',
                'status' => 'pending',
                'totalAmount' => -100.00, // Negative amount
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => (string) $supplier->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/totalAmount',
        ], $response);
    }

    public function test_store_validates_order_date_format(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                'orderDate' => 'invalid-date', // Invalid date format
                'status' => 'pending',
                'totalAmount' => 1500.00,
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => (string) $supplier->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/orderDate',
        ], $response);
    }

    public function test_unauthorized_user_cannot_create_purchase_order(): void
    {
        $supplier = Supplier::factory()->create();

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                'orderDate' => '2025-01-15',
                'status' => 'pending',
                'totalAmount' => 1500.00,
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => (string) $supplier->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_create_purchase_order(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs($user, 'sanctum');

        $data = [
            'type' => 'purchase-orders',
            'attributes' => [
                'orderDate' => '2025-01-15',
                'status' => 'pending',
                'totalAmount' => 1500.00,
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => (string) $supplier->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->post('/api/v1/purchase-orders');

        $response->assertStatus(403);
    }
}

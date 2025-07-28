<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_update_purchase_order(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $newSupplier = Supplier::factory()->create(['name' => 'New Supplier']);
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create([
            'order_date' => '2025-01-15',
            'status' => 'pending',
            'total_amount' => 1500.00,
            'notes' => 'Original notes'
        ]);

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => [
                'orderDate' => '2025-01-20',
                'status' => 'approved',
                'totalAmount' => 2000.75,
                'notes' => 'Updated purchase order notes',
            ],
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => (string) $newSupplier->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'purchase-orders',
                'id' => (string) $purchaseOrder->id,
                'attributes' => [
                    'orderDate' => '2025-01-20T00:00:00.000000Z',
                    'status' => 'approved',
                    'totalAmount' => '2000.75',
                    'notes' => 'Updated purchase order notes',
                ],
            ],
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'supplier_id' => $newSupplier->id,
            'order_date' => '2025-01-20',
            'status' => 'approved',
            'total_amount' => '2000.75',
            'notes' => 'Updated purchase order notes',
        ]);
    }

    public function test_admin_can_update_partial_purchase_order_data(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create([
            'order_date' => '2025-01-15',
            'status' => 'pending',
            'total_amount' => 1500.00,
            'notes' => 'Original notes'
        ]);

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => [
                'status' => 'approved',
                'notes' => 'Status updated to approved',
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'purchase-orders',
                'id' => (string) $purchaseOrder->id,
                'attributes' => [
                    'orderDate' => '2025-01-15T00:00:00.000000Z', // Unchanged
                    'status' => 'approved', // Updated
                    'totalAmount' => '1500.00', // Unchanged
                    'notes' => 'Status updated to approved', // Updated
                ],
            ],
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'supplier_id' => $supplier->id, // Unchanged
            'order_date' => '2025-01-15', // Unchanged
            'status' => 'approved', // Updated
            'total_amount' => '1500.00', // Unchanged
            'notes' => 'Status updated to approved', // Updated
        ]);
    }

    public function test_update_validates_status_enum(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => [
                'status' => 'invalid_status', // Invalid status
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/status',
        ], $response);
    }

    public function test_update_validates_total_amount_positive(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => [
                'totalAmount' => -500.00, // Negative amount
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/totalAmount',
        ], $response);
    }

    public function test_update_validates_supplier_exists(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'relationships' => [
                'supplier' => [
                    'data' => [
                        'type' => 'suppliers',
                        'id' => '999999', // Non-existent supplier
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

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

    public function test_update_validates_order_date_format(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => [
                'orderDate' => 'invalid-date-format', // Invalid date
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/orderDate',
        ], $response);
    }

    public function test_returns_404_for_nonexistent_purchase_order(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'purchase-orders',
            'id' => '999999',
            'attributes' => [
                'status' => 'approved',
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch('/api/v1/purchase-orders/999999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_update_purchase_order(): void
    {
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => [
                'status' => 'approved',
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_update_purchase_order(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();
        $this->actingAs($user, 'sanctum');

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => [
                'status' => 'approved',
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(403);
    }
}

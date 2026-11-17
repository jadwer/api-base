<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
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

        $contact = Contact::factory()->create(['is_supplier' => true]);
        $newContact = Contact::factory()->create(['is_supplier' => true, 'name' => 'New Supplier']);
        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'contact_id' => $contact->id,
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
                'totalAmount' => 2000.75,
                'notes' => 'Updated purchase order notes',
            ],
            'relationships' => [
                'contact' => [
                    'data' => [
                        'type' => 'contacts',
                        'id' => (string) $newContact->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        // Refactor ciclo (Patron 1): status readOnlyOnUpdate. El payload lo incluye
        // (como hace el form del FE) pero se IGNORA: la OC conserva 'pending'. Las
        // transiciones van por approve/reject/receive/cancel.
        $response->assertJson([
            'data' => [
                'type' => 'purchase-orders',
                'id' => (string) $purchaseOrder->id,
                'attributes' => [
                    'orderDate' => '2025-01-20',
                    'status' => 'pending',
                    'notes' => 'Updated purchase order notes',
                ],
            ],
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'contact_id' => $newContact->id,
            'status' => 'pending',
            'notes' => 'Updated purchase order notes',
        ]);
        
        // Verificar campos con formato específico por separado
        $updatedOrder = \Modules\Purchase\Models\PurchaseOrder::find($purchaseOrder->id);
        $this->assertNotNull($updatedOrder);
        // Total de solo lectura (22-10): el PATCH no lo cambia
        $this->assertEquals(1500.00, (float) $updatedOrder->total_amount);
        $this->assertEquals('2025-01-20', $updatedOrder->order_date->format('Y-m-d'));
    }

    public function test_admin_can_update_partial_purchase_order_data(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'order_date' => '2025-01-15',
            'status' => 'pending',
            'total_amount' => 1500.00,
            'notes' => 'Original notes'
        ]);

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => [
                'notes' => 'Status updated to approved',
            ],
        ];

        $response = $this->jsonApi()->withData($data)->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        // Refactor ciclo (Patron 1): el status del payload se IGNORA (readOnlyOnUpdate);
        // las notas si se actualizan. Transiciones por approve/reject/receive/cancel.
        $response->assertJson([
            'data' => [
                'type' => 'purchase-orders',
                'id' => (string) $purchaseOrder->id,
                'attributes' => [
                    'orderDate' => '2025-01-15', // Unchanged
                    'status' => 'pending', // Ignored: PATCH no cambia status
                    'totalAmount' => '1500.00', // Unchanged
                    'notes' => 'Status updated to approved', // Updated
                ],
            ],
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'contact_id' => $contact->id, // Unchanged
            'status' => 'pending', // Ignored: PATCH no cambia status
            'notes' => 'Status updated to approved', // Updated
        ]);
        
        // Verificar campos con formato específico por separado
        $updatedOrder = \Modules\Purchase\Models\PurchaseOrder::find($purchaseOrder->id);
        $this->assertNotNull($updatedOrder);
        $this->assertEquals(1500.00, (float) $updatedOrder->total_amount);
        $this->assertEquals('2025-01-15', $updatedOrder->order_date->format('Y-m-d'));
    }

    public function test_status_on_patch_is_rejected_in_spanish(): void
    {
        // Rediseno de Compras (17-11): el estatus ya no se ignora en silencio; se
        // rechaza con mensaje. Las transiciones van por approve/reject/receive/cancel.
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
        ]);

        foreach (['received', 'pending'] as $status) {
            $response = $this->jsonApi()->withData([
                'type' => 'purchase-orders',
                'id' => (string) $purchaseOrder->id,
                'attributes' => ['status' => $status],
            ])->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

            $response->assertStatus(422);
            $this->assertSame('El estatus cambia con las acciones Aprobar, Recibir o Cancelar.', $response->json('errors.0.detail'));
        }
        $this->assertDatabaseHas('purchase_orders', ['id' => $purchaseOrder->id, 'status' => 'pending']);
    }

    public function test_order_number_on_patch_is_rejected(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'pending']);

        $response = $this->jsonApi()->withData([
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'attributes' => ['orderNumber' => 'OC-MANUAL'],
        ])->patch("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(422);
        $this->assertSame('El número de orden lo asigna el sistema.', $response->json('errors.0.detail'));
    }

    public function test_update_validates_contact_exists(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);

        $data = [
            'type' => 'purchase-orders',
            'id' => (string) $purchaseOrder->id,
            'relationships' => [
                'contact' => [
                    'data' => [
                        'type' => 'contacts',
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

        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);

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
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);

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
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);
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

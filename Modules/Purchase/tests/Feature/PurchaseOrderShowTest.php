<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_show_purchase_order(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'order_date' => '2025-01-15',
            'status' => 'pending',
            'total_amount' => 1500.00,
            'notes' => 'Test purchase order notes'
        ]);

        $response = $this->jsonApi()->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'orderNumber',
                    'contactId',
                    'orderDate',
                    'status',
                    'totalAmount',
                    'notes',
                    'createdAt',
                    'updatedAt',
                ],
                'relationships' => [
                    'contact' => [
                        'links'
                    ],
                    'purchaseOrderItems' => [
                        'links'
                    ],
                ],
                'links'
            ],
            'jsonapi',
            'links'
        ]);

        $response->assertJson([
            'data' => [
                'id' => (string) $purchaseOrder->id,
                'type' => 'purchase-orders',
                'attributes' => [
                    'contactId' => $contact->id,
                    'orderDate' => '2025-01-15T00:00:00.000000Z',
                    'status' => 'pending',
                    'totalAmount' => '1500.00',
                    'notes' => 'Test purchase order notes',
                ],
            ],
        ]);
    }

    public function test_admin_can_show_purchase_order_with_contact_included(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $contact = Contact::factory()->create(['is_supplier' => true, 'name' => 'Test Supplier']);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);

        $response = $this->jsonApi()
            ->includePaths('contact')
            ->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        $this->assertNotEmpty($response->json('included'));
        
        $included = collect($response->json('included'));
        $contactData = $included->firstWhere('type', 'contacts');
        
        $this->assertEquals($contact->id, $contactData['id']);
        $this->assertEquals('Test Supplier', $contactData['attributes']['name']);
    }

    public function test_admin_can_show_purchase_order_with_items_included(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);

        $response = $this->jsonApi()
            ->includePaths('purchaseOrderItems')
            ->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertOk();
        // Note: purchaseOrderItems will be empty array since no items created
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'purchaseOrderItems' => [
                        'data'
                    ],
                ],
            ],
        ]);
    }

    public function test_returns_404_for_nonexistent_purchase_order(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/purchase-orders/999999');

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_show_purchase_order(): void
    {
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);

        $response = $this->jsonApi()->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_show_purchase_order(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);
        $this->actingAs($user, 'sanctum');

        $response = $this->jsonApi()->get("/api/v1/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(403);
    }
}

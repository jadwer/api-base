<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test as TestAttribute;

class SupplierUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    
    public function test_admin_can_update_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@supplier.com',
        ]);

        $data = [
            'type' => 'suppliers',
            'id' => (string) $supplier->id,
            'attributes' => [
                'name' => 'Updated Name',
                'email' => 'updated@supplier.com',
                'phone' => '+9876543210',
                'address' => '456 Updated Street',
                'rfc' => 'UPD123456ABC',
                'isActive' => false,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->patch("/api/v1/suppliers/{$supplier->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'email',
                    'phone',
                    'address',
                    'rfc',
                    'isActive',
                    'createdAt',
                    'updatedAt',
                ],
            ],
            'jsonapi',
        ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Name',
            'email' => 'updated@supplier.com',
            'phone' => '+9876543210',
            'address' => '456 Updated Street',
            'rfc' => 'UPD123456ABC',
            'is_active' => false,
        ]);
    }

    
    public function test_admin_can_partially_update_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@supplier.com',
            'phone' => '+1234567890',
        ]);

        $data = [
            'type' => 'suppliers',
            'id' => (string) $supplier->id,
            'attributes' => [
                'name' => 'Partially Updated Name',
                // Only updating name, leaving other fields unchanged
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->patch("/api/v1/suppliers/{$supplier->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Partially Updated Name',
            'email' => 'original@supplier.com', // Unchanged
            'phone' => '+1234567890', // Unchanged
        ]);
    }

    
    public function test_admin_can_toggle_supplier_active_status(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create(['is_active' => true]);

        $data = [
            'type' => 'suppliers',
            'id' => (string) $supplier->id,
            'attributes' => [
                'isActive' => false,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->patch("/api/v1/suppliers/{$supplier->id}");

        $response->assertOk();
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);
    }

    
    public function test_update_validates_email_format(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $supplier = Supplier::factory()->create();

        $data = [
            'type' => 'suppliers',
            'id' => (string) $supplier->id,
            'attributes' => [
                'email' => 'invalid-email-format',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->patch("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/email',
        ], $response);
    }

    
    public function test_update_validates_unique_email(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create two suppliers
        $supplier1 = Supplier::factory()->create(['email' => 'existing@supplier.com']);
        $supplier2 = Supplier::factory()->create(['email' => 'another@supplier.com']);

        $data = [
            'type' => 'suppliers',
            'id' => (string) $supplier2->id,
            'attributes' => [
                'email' => 'existing@supplier.com', // Trying to use email from supplier1
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->patch("/api/v1/suppliers/{$supplier2->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/email',
        ], $response);
    }

    
    public function test_returns_404_for_nonexistent_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'suppliers',
            'id' => '999999',
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->patch('/api/v1/suppliers/999999');

        $response->assertNotFound();
    }

    
    public function test_unauthorized_user_cannot_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $data = [
            'type' => 'suppliers',
            'id' => (string) $supplier->id,
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->patch("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(401);
    }

    
    public function test_user_without_permission_cannot_update_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs($user, 'sanctum');

        $data = [
            'type' => 'suppliers',
            'id' => (string) $supplier->id,
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->patch("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(403);
    }
}

<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test as TestAttribute;

class SupplierStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    
    public function test_admin_can_create_supplier(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'suppliers',
            'attributes' => [
                'name' => 'Test Supplier',
                'email' => 'test@supplier.com',
                'phone' => '+1234567890',
                'address' => '123 Test Street',
                'rfc' => 'TEST123456ABC',
                'isActive' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->post('/api/v1/suppliers');

        $response->assertCreated();
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
            'name' => 'Test Supplier',
            'email' => 'test@supplier.com',
            'rfc' => 'TEST123456ABC',
            'is_active' => true,
        ]);
    }

    
    public function test_admin_can_create_supplier_without_optional_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'suppliers',
            'attributes' => [
                'name' => 'Minimal Supplier',
                'isActive' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->post('/api/v1/suppliers');

        $response->assertCreated();
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Minimal Supplier',
            'email' => null,
            'phone' => null,
            'address' => null,
            'rfc' => null,
            'is_active' => true,
        ]);
    }

    
    public function test_store_validates_required_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $invalidData = [
            'type' => 'suppliers',
            'attributes' => (object) [], // Empty object for required field validation
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($invalidData)
            ->post('/api/v1/suppliers');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
        ], $response);
    }

    
    public function test_store_validates_email_format(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'suppliers',
            'attributes' => [
                'name' => 'Test Supplier',
                'email' => 'invalid-email-format',
                'isActive' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->post('/api/v1/suppliers');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/email',
        ], $response);
    }

    
    public function test_store_validates_unique_email(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create existing supplier
        Supplier::factory()->create(['email' => 'existing@supplier.com']);

        $data = [
            'type' => 'suppliers',
            'attributes' => [
                'name' => 'New Supplier',
                'email' => 'existing@supplier.com', // Duplicate email
                'isActive' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->post('/api/v1/suppliers');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/email',
        ], $response);
    }

    
    public function test_unauthorized_user_cannot_create_supplier(): void
    {
        $data = [
            'type' => 'suppliers',
            'attributes' => [
                'name' => 'Test Supplier',
                'isActive' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->post('/api/v1/suppliers');

        $response->assertStatus(401);
    }

    
    public function test_user_without_permission_cannot_create_supplier(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $data = [
            'type' => 'suppliers',
            'attributes' => [
                'name' => 'Test Supplier',
                'isActive' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('suppliers')
            ->withData($data)
            ->post('/api/v1/suppliers');

        $response->assertStatus(403);
    }
}

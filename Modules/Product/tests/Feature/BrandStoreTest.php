<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BrandStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_create_brand(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'brands',
            'attributes' => [
                'name' => 'Test Brand',
                'description' => 'A test brand description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/brands');

        $response->assertCreated()->assertJson([
            'data' => [
                'type' => 'brands',
                'attributes' => [
                    'name' => 'Test Brand',
                    'description' => 'A test brand description',
                ],
            ],
        ]);

        $this->assertDatabaseHas('brands', [
            'name' => 'Test Brand',
            'description' => 'A test brand description',
        ]);
    }

    public function test_customer_cannot_create_brand(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $data = [
            'type' => 'brands',
            'attributes' => [
                'name' => 'Test Brand',
                'description' => 'A test brand description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/brands');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_brand(): void
    {
        $data = [
            'type' => 'brands',
            'attributes' => [
                'name' => 'Test Brand',
                'description' => 'A test brand description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/brands');

        $response->assertStatus(401);
    }

    public function test_brand_creation_fails_with_missing_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'brands',
            'attributes' => [
                // Missing required fields
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/brands');

        $response->assertStatus(400);
    }

    public function test_brand_creation_fails_with_duplicate_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create first brand
        Brand::factory()->create(['name' => 'Duplicate Brand']);

        $data = [
            'type' => 'brands',
            'attributes' => [
                'name' => 'Duplicate Brand',
                'description' => 'Second brand with same name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/brands');

        $response->assertStatus(422);
    }
}

<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BrandUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_update_brand(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $brand = Brand::factory()->create([
            'name' => 'Original Brand',
            'description' => 'Original description',
        ]);

        $data = [
            'type' => 'brands',
            'id' => (string) $brand->id,
            'attributes' => [
                'name' => 'Updated Brand Name',
                'description' => 'Updated description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/brands/{$brand->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'type' => 'brands',
                'id' => (string) $brand->id,
                'attributes' => [
                    'name' => 'Updated Brand Name',
                    'description' => 'Updated description',
                ],
            ],
        ]);

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Brand Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_customer_cannot_update_brand(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $brand = Brand::factory()->create();

        $data = [
            'type' => 'brands',
            'id' => (string) $brand->id,
            'attributes' => [
                'name' => 'Updated Brand Name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/brands/{$brand->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_brand(): void
    {
        $brand = Brand::factory()->create();

        $data = [
            'type' => 'brands',
            'id' => (string) $brand->id,
            'attributes' => [
                'name' => 'Updated Brand Name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/brands/{$brand->id}");

        $response->assertStatus(401);
    }

    public function test_brand_update_fails_with_duplicate_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create existing brand with name
        Brand::factory()->create(['name' => 'Existing Brand']);

        // Create brand to update
        $brandToUpdate = Brand::factory()->create(['name' => 'Different Brand']);

        $data = [
            'type' => 'brands',
            'id' => (string) $brandToUpdate->id,
            'attributes' => [
                'name' => 'Existing Brand', // Try to use existing name
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/brands/{$brandToUpdate->id}");

        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
        ], $response);
    }

    public function test_brand_update_allows_keeping_same_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $brand = Brand::factory()->create([
            'name' => 'Same Brand Name',
            'description' => 'Original description',
        ]);

        $data = [
            'type' => 'brands',
            'id' => (string) $brand->id,
            'attributes' => [
                'name' => 'Same Brand Name', // Keep the same name
                'description' => 'Updated description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/brands/{$brand->id}");

        $response->assertOk();
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Same Brand Name',
            'description' => 'Updated description',
        ]);
    }
}

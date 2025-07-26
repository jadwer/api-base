<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BrandShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_authenticated_user_can_view_brand(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'description' => 'Test description',
        ]);

        $response = $this->jsonApi()->get("/api/v1/brands/{$brand->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $brand->id,
                'type' => 'brands',
                'attributes' => [
                    'name' => 'Test Brand',
                    'description' => 'Test description',
                ],
            ],
        ]);
    }

    public function test_unauthenticated_user_cannot_view_brand(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/brands/{$brand->id}");

        $response->assertStatus(401);
    }

    public function test_customer_can_view_brand(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $brand = Brand::factory()->create([
            'name' => 'Customer Brand',
            'description' => 'Customer description',
        ]);

        $response = $this->jsonApi()->get("/api/v1/brands/{$brand->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $brand->id,
                'type' => 'brands',
                'attributes' => [
                    'name' => 'Customer Brand',
                    'description' => 'Customer description',
                ],
            ],
        ]);
    }

    public function test_can_include_brand_products(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $brand = Brand::factory()
            ->hasProducts(2)
            ->create(['name' => 'Brand with Products']);

        $response = $this->jsonApi()->get("/api/v1/brands/{$brand->id}?include=products");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'description',
                ],
                'relationships' => [
                    'products' => [
                        'data' => [
                            '*' => [
                                'id',
                                'type'
                            ]
                        ]
                    ]
                ],
            ],
            'included'
        ]);
    }
}

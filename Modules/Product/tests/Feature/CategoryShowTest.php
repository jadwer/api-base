<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_authenticated_user_can_view_category(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $category = Category::factory()->create([
            'name' => 'Test Category',
            'description' => 'Test description',
        ]);

        $response = $this->jsonApi()->get("/api/v1/categories/{$category->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $category->id,
                'type' => 'categories',
                'attributes' => [
                    'name' => 'Test Category',
                    'description' => 'Test description',
                ],
            ],
        ]);
    }

    public function test_unauthenticated_user_cannot_view_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/categories/{$category->id}");

        $response->assertStatus(401);
    }

    public function test_customer_can_view_category(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $category = Category::factory()->create([
            'name' => 'Customer Category',
            'description' => 'Customer description',
        ]);

        $response = $this->jsonApi()->get("/api/v1/categories/{$category->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $category->id,
                'type' => 'categories',
                'attributes' => [
                    'name' => 'Customer Category',
                    'description' => 'Customer description',
                ],
            ],
        ]);
    }

    public function test_can_include_category_products(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $category = Category::factory()
            ->hasProducts(2)
            ->create(['name' => 'Category with Products']);

        $response = $this->jsonApi()->get("/api/v1/categories/{$category->id}?include=products");

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

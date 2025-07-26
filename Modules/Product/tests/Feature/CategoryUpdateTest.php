<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $category = Category::factory()->create([
            'name' => 'Original Category',
            'description' => 'Original description',
        ]);

        $data = [
            'type' => 'categories',
            'id' => (string) $category->id,
            'attributes' => [
                'name' => 'Updated Category Name',
                'description' => 'Updated description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/categories/{$category->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'type' => 'categories',
                'id' => (string) $category->id,
                'attributes' => [
                    'name' => 'Updated Category Name',
                    'description' => 'Updated description',
                ],
            ],
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_customer_cannot_update_category(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $category = Category::factory()->create();

        $data = [
            'type' => 'categories',
            'id' => (string) $category->id,
            'attributes' => [
                'name' => 'Updated Category Name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_category(): void
    {
        $category = Category::factory()->create();

        $data = [
            'type' => 'categories',
            'id' => (string) $category->id,
            'attributes' => [
                'name' => 'Updated Category Name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/categories/{$category->id}");

        $response->assertStatus(401);
    }

    public function test_category_update_fails_with_duplicate_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create existing category with name
        Category::factory()->create(['name' => 'Existing Category']);

        // Create category to update
        $categoryToUpdate = Category::factory()->create(['name' => 'Different Category']);

        $data = [
            'type' => 'categories',
            'id' => (string) $categoryToUpdate->id,
            'attributes' => [
                'name' => 'Existing Category', // Try to use existing name
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/categories/{$categoryToUpdate->id}");

        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
        ], $response);
    }

    public function test_category_update_allows_keeping_same_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $category = Category::factory()->create([
            'name' => 'Same Category Name',
            'description' => 'Original description',
        ]);

        $data = [
            'type' => 'categories',
            'id' => (string) $category->id,
            'attributes' => [
                'name' => 'Same Category Name', // Keep the same name
                'description' => 'Updated description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/categories/{$category->id}");

        $response->assertOk();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Same Category Name',
            'description' => 'Updated description',
        ]);
    }
}

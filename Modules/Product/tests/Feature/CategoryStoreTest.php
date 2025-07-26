<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'categories',
            'attributes' => [
                'name' => 'Test Category',
                'description' => 'A test category description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/categories');

        $response->assertCreated()->assertJson([
            'data' => [
                'type' => 'categories',
                'attributes' => [
                    'name' => 'Test Category',
                    'description' => 'A test category description',
                ],
            ],
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'description' => 'A test category description',
        ]);
    }

    public function test_customer_cannot_create_category(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $data = [
            'type' => 'categories',
            'attributes' => [
                'name' => 'Test Category',
                'description' => 'A test category description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/categories');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_category(): void
    {
        $data = [
            'type' => 'categories',
            'attributes' => [
                'name' => 'Test Category',
                'description' => 'A test category description',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/categories');

        $response->assertStatus(401);
    }

    public function test_category_creation_fails_with_missing_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'categories',
            'attributes' => [
                // Missing required fields
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/categories');

        $response->assertStatus(400);
    }

    public function test_category_creation_fails_with_duplicate_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create first category
        Category::factory()->create(['name' => 'Duplicate Category']);

        $data = [
            'type' => 'categories',
            'attributes' => [
                'name' => 'Duplicate Category',
                'description' => 'Second category with same name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/categories');

        $response->assertStatus(422);
    }
}

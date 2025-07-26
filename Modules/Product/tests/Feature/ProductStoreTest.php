<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $data = [
            'type' => 'products',
            'attributes' => [
                'name' => 'Test Product',
                'sku' => 'TEST-001',
                'description' => 'A test product',
                'fullDescription' => 'A complete description of the test product',
                'price' => 99.99,
                'cost' => 50.00,
                'iva' => true,
                'imgPath' => '/images/test-product.jpg',
                'datasheetPath' => '/docs/test-product.pdf',
            ],
            'relationships' => [
                'unit' => [
                    'data' => ['type' => 'units', 'id' => (string) $unit->id]
                ],
                'category' => [
                    'data' => ['type' => 'categories', 'id' => (string) $category->id]
                ],
                'brand' => [
                    'data' => ['type' => 'brands', 'id' => (string) $brand->id]
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/products');

        $response->assertCreated();
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $data = [
            'type' => 'products',
            'attributes' => [
                'name' => 'Test Product',
                'sku' => 'TEST-001',
                'price' => 99.99,
            ],
            'relationships' => [
                'unit' => ['data' => ['type' => 'units', 'id' => (string) $unit->id]],
                'category' => ['data' => ['type' => 'categories', 'id' => (string) $category->id]],
                'brand' => ['data' => ['type' => 'brands', 'id' => (string) $brand->id]],
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/products');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_product(): void
    {
        $response = $this->jsonApi()
            ->withData([
                'type' => 'products',
                'attributes' => ['name' => 'Test Product'],
            ])
            ->post('/api/v1/products');

        $response->assertStatus(401);
    }

    public function test_product_creation_fails_with_missing_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()
            ->withData([
                'type' => 'products',
                'attributes' => new \stdClass(), // empty attributes
            ])
            ->post('/api/v1/products');

        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
            '/data/attributes/sku',
            '/data/attributes/price',
        ], $response);
    }

    public function test_product_creation_fails_with_duplicate_sku(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        Product::factory()->create([
            'sku' => 'DUPLICATE-SKU',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $data = [
            'type' => 'products',
            'attributes' => [
                'name' => 'Another Product',
                'sku' => 'DUPLICATE-SKU',
                'price' => 99.99,
            ],
            'relationships' => [
                'unit' => ['data' => ['type' => 'units', 'id' => (string) $unit->id]],
                'category' => ['data' => ['type' => 'categories', 'id' => (string) $category->id]],
                'brand' => ['data' => ['type' => 'brands', 'id' => (string) $brand->id]],
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/products');

        $this->assertJsonApiValidationErrors([
            '/data/attributes/sku',
        ], $response);
    }
}

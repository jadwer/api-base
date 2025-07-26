<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_update_product(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'name' => 'Original Product',
            'sku' => 'ORIG-001',
            'price' => 99.99,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $newUnit = Unit::factory()->create();
        $newCategory = Category::factory()->create();
        $newBrand = Brand::factory()->create();

        $data = [
            'type' => 'products',
            'id' => (string) $product->id,
            'attributes' => [
                'name' => 'Updated Product Name',
                'sku' => 'UPD-001',
                'description' => 'Updated description',
                'fullDescription' => 'Updated full description',
                'price' => 149.99,
                'cost' => 75.00,
                'iva' => false,
                'imgPath' => '/images/updated-product.jpg',
                'datasheetPath' => '/docs/updated-product.pdf',
            ],
            'relationships' => [
                'unit' => [
                    'data' => ['type' => 'units', 'id' => (string) $newUnit->id]
                ],
                'category' => [
                    'data' => ['type' => 'categories', 'id' => (string) $newCategory->id]
                ],
                'brand' => [
                    'data' => ['type' => 'brands', 'id' => (string) $newBrand->id]
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/products/{$product->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'type' => 'products',
                'id' => (string) $product->id,
                'attributes' => [
                    'name' => 'Updated Product Name',
                    'sku' => 'UPD-001',
                    'price' => 149.99,
                    'iva' => false,
                ],
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'sku' => 'UPD-001',
            'price' => 149.99,
            'unit_id' => $newUnit->id,
            'category_id' => $newCategory->id,
            'brand_id' => $newBrand->id,
        ]);
    }

    public function test_customer_cannot_update_product(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $data = [
            'type' => 'products',
            'id' => (string) $product->id,
            'attributes' => [
                'name' => 'Updated Product Name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/products/{$product->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_product(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $data = [
            'type' => 'products',
            'id' => (string) $product->id,
            'attributes' => [
                'name' => 'Updated Product Name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/products/{$product->id}");

        $response->assertStatus(401);
    }

    public function test_product_update_fails_with_duplicate_sku(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        // Create existing product with SKU
        Product::factory()->create([
            'sku' => 'EXISTING-SKU',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        // Create product to update
        $productToUpdate = Product::factory()->create([
            'sku' => 'DIFFERENT-SKU',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $data = [
            'type' => 'products',
            'id' => (string) $productToUpdate->id,
            'attributes' => [
                'sku' => 'EXISTING-SKU', // Try to use existing SKU
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/products/{$productToUpdate->id}");

        $this->assertJsonApiValidationErrors([
            '/data/attributes/sku',
        ], $response);
    }

    public function test_product_update_allows_keeping_same_sku(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'name' => 'Original Name',
            'sku' => 'SAME-SKU',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $data = [
            'type' => 'products',
            'id' => (string) $product->id,
            'attributes' => [
                'name' => 'Updated Name',
                'sku' => 'SAME-SKU', // Keep the same SKU
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/products/{$product->id}");

        $response->assertOk();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'sku' => 'SAME-SKU',
        ]);
    }
}

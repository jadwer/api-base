<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_authenticated_user_can_view_product(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create(['name' => 'Test Kilogram', 'code' => 'test_kg']);
        $category = Category::factory()->create(['name' => 'Test Electronics']);
        $brand = Brand::factory()->create(['name' => 'Test Samsung']);

        $product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'description' => 'Test description',
            'price' => 199.99,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->get("/api/v1/products/{$product->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $product->id,
                'type' => 'products',
                'attributes' => [
                    'name' => 'Test Product',
                    'sku' => 'TEST-001',
                    'description' => 'Test description',
                    'price' => 199.99,
                ]
            ]
        ]);
    }

    public function test_unauthenticated_user_cannot_view_product(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->get("/api/v1/products/{$product->id}");

        $response->assertStatus(401);
    }

    public function test_customer_can_view_product(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'name' => 'Customer Product',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->get("/api/v1/products/{$product->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $product->id,
                'type' => 'products',
                'attributes' => [
                    'name' => 'Customer Product',
                ]
            ]
        ]);
    }

    public function test_can_include_product_relationships(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->get("/api/v1/products/{$product->id}?include=unit,category,brand");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'unit' => ['data' => ['id', 'type']],
                    'category' => ['data' => ['id', 'type']],
                    'brand' => ['data' => ['id', 'type']],
                ]
            ],
            'included' => [
                '*' => ['id', 'type', 'attributes']
            ]
        ]);
    }
}

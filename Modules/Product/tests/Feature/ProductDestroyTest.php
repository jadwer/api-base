<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'name' => 'Product to Delete',
            'sku' => 'DEL-001',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->delete("/api/v1/products/{$product->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_customer_cannot_delete_product(): void
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

        $response = $this->jsonApi()->delete("/api/v1/products/{$product->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_product(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->delete("/api/v1/products/{$product->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_delete_nonexistent_product_returns_404(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->delete("/api/v1/products/99999");

        $response->assertNotFound();
    }

    public function test_god_role_can_delete_product(): void
    {
        $god = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($god, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'name' => 'Product for God to Delete',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->delete("/api/v1/products/{$product->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }
}

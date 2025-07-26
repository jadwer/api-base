<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $category = Category::factory()->create([
            'name' => 'Category to Delete',
        ]);

        $response = $this->jsonApi()->delete("/api/v1/categories/{$category->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_customer_cannot_delete_category(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $category = Category::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/categories/{$category->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/categories/{$category->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_delete_nonexistent_category_returns_404(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->delete("/api/v1/categories/99999");

        $response->assertNotFound();
    }

    public function test_cannot_delete_category_with_associated_products(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $category = Category::factory()->create();
        $unit = \Modules\Product\Models\Unit::factory()->create();
        $brand = \Modules\Product\Models\Brand::factory()->create();
        
        // Create a product that uses this category
        Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->delete("/api/v1/categories/{$category->id}");

        // This should fail due to foreign key constraint
        $response->assertStatus(500);
        
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }
}

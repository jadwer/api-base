<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BrandDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_delete_brand(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $brand = Brand::factory()->create([
            'name' => 'Brand to Delete',
        ]);

        $response = $this->jsonApi()->delete("/api/v1/brands/{$brand->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_customer_cannot_delete_brand(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $brand = Brand::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/brands/{$brand->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_brand(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/brands/{$brand->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_delete_nonexistent_brand_returns_404(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->delete("/api/v1/brands/99999");

        $response->assertNotFound();
    }

    public function test_cannot_delete_brand_with_associated_products(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $brand = Brand::factory()->create();
        $unit = \Modules\Product\Models\Unit::factory()->create();
        $category = \Modules\Product\Models\Category::factory()->create();
        
        // Create a product that uses this brand
        Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->delete("/api/v1/brands/{$brand->id}");

        // This should fail due to foreign key constraint
        $response->assertStatus(500);
        
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
        ]);
    }
}

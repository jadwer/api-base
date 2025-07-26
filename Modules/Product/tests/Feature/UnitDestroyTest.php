<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnitDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_delete_unit(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create([
            'name' => 'Unit to Delete',
            'code' => 'del_unit',
        ]);

        $response = $this->jsonApi()->delete("/api/v1/units/{$unit->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('units', [
            'id' => $unit->id,
        ]);
    }

    public function test_customer_cannot_delete_unit(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $unit = Unit::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/units/{$unit->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_unit(): void
    {
        $unit = Unit::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/units/{$unit->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
        ]);
    }

    public function test_delete_nonexistent_unit_returns_404(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->delete("/api/v1/units/99999");

        $response->assertNotFound();
    }

    public function test_cannot_delete_unit_with_associated_products(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = \Modules\Product\Models\Category::factory()->create();
        $brand = \Modules\Product\Models\Brand::factory()->create();
        
        // Create a product that uses this unit
        Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->delete("/api/v1/units/{$unit->id}");

        // This should fail due to foreign key constraint
        $response->assertStatus(500);
        
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
        ]);
    }
}

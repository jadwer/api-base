<?php

namespace Modules\Product\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductBulkPriceUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Brand $brand;
    protected Category $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'products.update', 'guard_name' => 'api']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(['products.update']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->unit = Unit::factory()->create();
        $this->brand = Brand::factory()->create(['is_active' => true]);
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'unit_id' => $this->unit->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ], $overrides));
    }

    public function test_admin_can_increase_prices_by_percentage(): void
    {
        $p1 = $this->createProduct(['price' => 100.00]);
        $p2 = $this->createProduct(['price' => 200.00]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-price-update', [
                'brand_id' => $this->brand->id,
                'percentage' => 10,
                'operation' => 'increase',
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'affected_count' => 2,
                'operation' => 'increase',
                'percentage' => 10,
            ]);

        $this->assertEquals(110.00, $p1->fresh()->price);
        $this->assertEquals(220.00, $p2->fresh()->price);
    }

    public function test_admin_can_decrease_prices_by_percentage(): void
    {
        $p1 = $this->createProduct(['price' => 100.00]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-price-update', [
                'brand_id' => $this->brand->id,
                'percentage' => 25,
                'operation' => 'decrease',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['affected_count' => 1]);

        $this->assertEquals(75.00, $p1->fresh()->price);
    }

    public function test_validates_brand_exists(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-price-update', [
                'brand_id' => 99999,
                'percentage' => 10,
                'operation' => 'increase',
            ]);

        $response->assertUnprocessable();
    }

    public function test_validates_percentage_range(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-price-update', [
                'brand_id' => $this->brand->id,
                'percentage' => 150,
                'operation' => 'increase',
            ]);

        $response->assertUnprocessable();
    }

    public function test_validates_operation_enum(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-price-update', [
                'brand_id' => $this->brand->id,
                'percentage' => 10,
                'operation' => 'multiply',
            ]);

        $response->assertUnprocessable();
    }

    public function test_guest_cannot_bulk_update_prices(): void
    {
        $response = $this->postJson('/api/v1/products/bulk-price-update', [
            'brand_id' => $this->brand->id,
            'percentage' => 10,
            'operation' => 'increase',
        ]);

        $response->assertUnauthorized();
    }

    public function test_price_calculation_rounds_correctly(): void
    {
        $p1 = $this->createProduct(['price' => 33.33]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-price-update', [
                'brand_id' => $this->brand->id,
                'percentage' => 3,
                'operation' => 'increase',
            ]);

        // 33.33 * 1.03 = 34.3299 -> rounded to 34.33
        $this->assertEquals(34.33, $p1->fresh()->price);
    }

    public function test_returns_correct_affected_count(): void
    {
        $this->createProduct(['price' => 100.00]);
        $this->createProduct(['price' => 200.00]);

        $otherBrand = Brand::factory()->create(['is_active' => true]);
        $this->createProduct(['price' => 300.00, 'brand_id' => $otherBrand->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-price-update', [
                'brand_id' => $this->brand->id,
                'percentage' => 5,
                'operation' => 'increase',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['affected_count' => 2]);
    }
}

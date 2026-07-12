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

class ProductBulkAssignCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guest;
    protected Brand $brand;
    protected Category $sourceCategory;
    protected Category $targetCategory;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'products.update', 'guard_name' => 'api']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(['products.update']);

        // Role without the products.update permission.
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->guest = User::factory()->create();
        $this->guest->assignRole('customer');

        $this->unit = Unit::factory()->create();
        $this->brand = Brand::factory()->create(['is_active' => true]);
        $this->sourceCategory = Category::factory()->create(['is_active' => true]);
        $this->targetCategory = Category::factory()->create(['is_active' => true]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'unit_id' => $this->unit->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->sourceCategory->id,
        ], $overrides));
    }

    public function test_admin_can_reassign_category_by_brand(): void
    {
        $p1 = $this->createProduct();
        $p2 = $this->createProduct();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-assign-category-by-brand', [
                'brand_id' => $this->brand->id,
                'category_id' => $this->targetCategory->id,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['affected_count' => 2]);

        $this->assertEquals($this->targetCategory->id, $p1->fresh()->category_id);
        $this->assertEquals($this->targetCategory->id, $p2->fresh()->category_id);
    }

    public function test_only_products_of_target_brand_are_affected(): void
    {
        $inBrand1 = $this->createProduct();
        $inBrand2 = $this->createProduct();

        $otherBrand = Brand::factory()->create(['is_active' => true]);
        $otherProduct = $this->createProduct([
            'brand_id' => $otherBrand->id,
            'category_id' => $this->sourceCategory->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-assign-category-by-brand', [
                'brand_id' => $this->brand->id,
                'category_id' => $this->targetCategory->id,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['affected_count' => 2]);

        $this->assertEquals($this->targetCategory->id, $inBrand1->fresh()->category_id);
        $this->assertEquals($this->targetCategory->id, $inBrand2->fresh()->category_id);
        // Product from another brand keeps its original category.
        $this->assertEquals($this->sourceCategory->id, $otherProduct->fresh()->category_id);
    }

    public function test_guest_cannot_reassign_category(): void
    {
        $response = $this->postJson('/api/v1/products/bulk-assign-category-by-brand', [
            'brand_id' => $this->brand->id,
            'category_id' => $this->targetCategory->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_reassign_category(): void
    {
        $response = $this->actingAs($this->guest, 'sanctum')
            ->postJson('/api/v1/products/bulk-assign-category-by-brand', [
                'brand_id' => $this->brand->id,
                'category_id' => $this->targetCategory->id,
            ]);

        $response->assertForbidden();
    }

    public function test_validates_brand_exists(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-assign-category-by-brand', [
                'brand_id' => 99999,
                'category_id' => $this->targetCategory->id,
            ]);

        $response->assertUnprocessable();
    }

    public function test_validates_category_exists(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-assign-category-by-brand', [
                'brand_id' => $this->brand->id,
                'category_id' => 99999,
            ]);

        $response->assertUnprocessable();
    }
}

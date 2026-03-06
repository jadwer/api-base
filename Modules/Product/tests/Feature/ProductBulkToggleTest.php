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

class ProductBulkToggleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Brand $brand;
    protected Category $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'products.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'brands.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'categories.update', 'guard_name' => 'api']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(['products.update', 'brands.update', 'categories.update']);

        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

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
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_can_bulk_toggle_products_inactive(): void
    {
        $p1 = $this->createProduct();
        $p2 = $this->createProduct();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-toggle-active', [
                'product_ids' => [$p1->id, $p2->id],
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['affected_count' => 2]);

        $this->assertFalse($p1->fresh()->is_active);
        $this->assertFalse($p2->fresh()->is_active);
    }

    public function test_admin_can_bulk_toggle_products_active(): void
    {
        $p1 = $this->createProduct(['is_active' => false]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-toggle-active', [
                'product_ids' => [$p1->id],
                'is_active' => true,
            ]);

        $response->assertOk();
        $this->assertTrue($p1->fresh()->is_active);
    }

    public function test_admin_can_bulk_toggle_by_brand(): void
    {
        $this->createProduct();
        $this->createProduct();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-toggle-by-brand', [
                'brand_id' => $this->brand->id,
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['affected_count' => 2]);
    }

    public function test_admin_can_bulk_toggle_by_category(): void
    {
        $this->createProduct();
        $this->createProduct();
        $this->createProduct();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-toggle-by-category', [
                'category_id' => $this->category->id,
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['affected_count' => 3]);
    }

    public function test_admin_can_toggle_brand_active_without_products(): void
    {
        $this->createProduct();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/brands/{$this->brand->id}/toggle-active", [
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['brand_updated' => true, 'products_affected' => 0]);

        $this->assertFalse($this->brand->fresh()->is_active);
    }

    public function test_admin_can_toggle_brand_active_with_products(): void
    {
        $p1 = $this->createProduct();
        $p2 = $this->createProduct();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/brands/{$this->brand->id}/toggle-active", [
                'is_active' => false,
                'include_products' => true,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['brand_updated' => true, 'products_affected' => 2]);

        $this->assertFalse($this->brand->fresh()->is_active);
        $this->assertFalse($p1->fresh()->is_active);
        $this->assertFalse($p2->fresh()->is_active);
    }

    public function test_admin_can_toggle_category_active_without_products(): void
    {
        $this->createProduct();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/categories/{$this->category->id}/toggle-active", [
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['category_updated' => true, 'products_affected' => 0]);

        $this->assertFalse($this->category->fresh()->is_active);
    }

    public function test_admin_can_toggle_category_active_with_products(): void
    {
        $p1 = $this->createProduct();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/categories/{$this->category->id}/toggle-active", [
                'is_active' => false,
                'include_products' => true,
            ]);

        $response->assertOk()
            ->assertJsonFragment(['products_affected' => 1]);

        $this->assertFalse($p1->fresh()->is_active);
    }

    public function test_guest_cannot_bulk_toggle(): void
    {
        $response = $this->postJson('/api/v1/products/bulk-toggle-active', [
            'product_ids' => [1],
            'is_active' => false,
        ]);

        $response->assertUnauthorized();
    }

    public function test_customer_cannot_bulk_toggle(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/products/bulk-toggle-active', [
                'product_ids' => [1],
                'is_active' => false,
            ]);

        $response->assertForbidden();
    }

    public function test_validates_product_ids_required(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-toggle-active', [
                'is_active' => false,
            ]);

        $response->assertUnprocessable();
    }

    public function test_validates_brand_id_exists(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/bulk-toggle-by-brand', [
                'brand_id' => 99999,
                'is_active' => false,
            ]);

        $response->assertUnprocessable();
    }
}

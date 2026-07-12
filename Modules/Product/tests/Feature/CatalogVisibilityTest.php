<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Tests\TestCase;

class CatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected Brand $activeBrand;
    protected Brand $inactiveBrand;
    protected Category $activeCategory;
    protected Category $inactiveCategory;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::factory()->create();
        $this->activeBrand = Brand::factory()->create(['is_active' => true]);
        $this->inactiveBrand = Brand::factory()->create(['is_active' => false]);
        $this->activeCategory = Category::factory()->create(['is_active' => true]);
        $this->inactiveCategory = Category::factory()->create(['is_active' => false]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'unit_id' => $this->unit->id,
            'brand_id' => $this->activeBrand->id,
            'category_id' => $this->activeCategory->id,
            'is_active' => true,
        ], $overrides));
    }

    public function test_public_catalog_hides_inactive_products(): void
    {
        $active = $this->createProduct(['name' => 'Active Product']);
        $this->createProduct(['name' => 'Inactive Product', 'is_active' => false]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $active->id, $ids);
    }

    public function test_public_catalog_hides_products_of_inactive_brand(): void
    {
        $visible = $this->createProduct(['name' => 'Visible']);
        $this->createProduct([
            'name' => 'Hidden by brand',
            'brand_id' => $this->inactiveBrand->id,
        ]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $visible->id, $ids);
        // Product with inactive brand should not appear
        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
        $this->assertNotContains('Hidden by brand', $names);
    }

    public function test_public_catalog_hides_products_of_inactive_category(): void
    {
        $visible = $this->createProduct(['name' => 'Cat Visible']);
        $this->createProduct([
            'name' => 'Cat Hidden',
            'category_id' => $this->inactiveCategory->id,
        ]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
        $this->assertContains('Cat Visible', $names);
        $this->assertNotContains('Cat Hidden', $names);
    }

    public function test_public_catalog_hides_internal_products(): void
    {
        // Public product (is_public true) -> visible
        $public = $this->createProduct(['name' => 'Public Product', 'is_public' => true]);

        // Internal product (is_public false) while still active -> hidden
        $this->createProduct([
            'name' => 'Internal Product',
            'is_public' => false,
            'is_active' => true,
        ]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $public->id, $ids);
        $this->assertContains('Public Product', $names);
        $this->assertNotContains('Internal Product', $names);
    }

    public function test_only_active_products_with_active_brand_and_category_appear(): void
    {
        // Active product, active brand, active category -> visible
        $visible = $this->createProduct(['name' => 'Fully Active']);

        // Active product, inactive brand -> hidden
        $this->createProduct([
            'name' => 'Inactive Brand Product',
            'brand_id' => $this->inactiveBrand->id,
        ]);

        // Active product, inactive category -> hidden
        $this->createProduct([
            'name' => 'Inactive Cat Product',
            'category_id' => $this->inactiveCategory->id,
        ]);

        // Inactive product, active brand/category -> hidden
        $this->createProduct([
            'name' => 'Inactive Product',
            'is_active' => false,
        ]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
        $this->assertContains('Fully Active', $names);
        $this->assertNotContains('Inactive Brand Product', $names);
        $this->assertNotContains('Inactive Cat Product', $names);
        $this->assertNotContains('Inactive Product', $names);
    }
}

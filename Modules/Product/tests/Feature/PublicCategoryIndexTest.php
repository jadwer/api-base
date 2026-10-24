<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Tests\TestCase;

class PublicCategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_count_only_includes_publicly_visible_products(): void
    {
        $unit = Unit::factory()->create();
        $brand = Brand::factory()->create(['is_active' => true]);
        $inactiveBrand = Brand::factory()->create(['is_active' => false]);
        $category = Category::factory()->create(['is_active' => true]);
        $base = ['unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id];

        Product::factory()->count(2)->create($base + ['is_active' => true, 'is_public' => true]);
        Product::factory()->create($base + ['is_active' => true, 'is_public' => false]);
        Product::factory()->create($base + ['is_active' => false, 'is_public' => true]);
        Product::factory()->create(['brand_id' => $inactiveBrand->id, 'is_active' => true, 'is_public' => true] + $base);

        $response = $this->jsonApi()->get('/api/public/v1/public-categories');

        $response->assertOk();
        $row = collect($response->json('data'))->firstWhere('id', (string) $category->id);
        $this->assertSame(2, $row['attributes']['productsCount']);
    }

    public function test_show_uses_the_same_public_count(): void
    {
        $unit = Unit::factory()->create();
        $brand = Brand::factory()->create(['is_active' => true]);
        $category = Category::factory()->create(['is_active' => true]);
        $base = ['unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id];

        Product::factory()->create($base + ['is_active' => true, 'is_public' => true]);
        Product::factory()->count(3)->create($base + ['is_active' => true, 'is_public' => false]);

        $response = $this->jsonApi()->get("/api/public/v1/public-categories/{$category->id}");

        $response->assertOk();
        $this->assertSame(1, $response->json('data.attributes.productsCount'));
    }
}

<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Services\RecommendationEngine;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * P2 granel en el catalogo publico: pricingType expuesto, filtro, rango de
 * precio sin los sujetos a cotizacion y orden por precio con ellos al final.
 */
class PublicProductPricingTypeTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->brand = Brand::factory()->create(['is_active' => true]);
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    private function product(array $overrides = [], bool $quoteOnly = false): Product
    {
        $factory = Product::factory();
        if ($quoteOnly) {
            $factory = $factory->quoteOnly();
        }

        return $factory->create(array_merge([
            'is_active' => true,
            'is_public' => true,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ], $overrides));
    }

    public function test_pricing_type_is_exposed_and_filterable(): void
    {
        $fixed = $this->product(['price' => 10]);
        $quoteOnly = $this->product([], true);

        $response = $this->jsonApi()->expects('public-products')
            ->get("/api/public/v1/public-products/{$quoteOnly->id}");
        $response->assertOk();
        $this->assertSame('quote_only', $response->json('data.attributes.pricingType'));
        $this->assertNull($response->json('data.attributes.price'));

        $response = $this->jsonApi()->expects('public-products')
            ->filter(['pricing_type' => 'fixed', 'brands' => (string) $this->brand->id])
            ->get('/api/public/v1/public-products');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains((string) $fixed->id, $ids);
        $this->assertNotContains((string) $quoteOnly->id, $ids);
    }

    public function test_price_range_excludes_quote_only(): void
    {
        $fixed = $this->product(['price' => 50]);
        $quoteOnly = $this->product([], true);

        $response = $this->jsonApi()->expects('public-products')
            ->filter(['price_min' => 0, 'brands' => (string) $this->brand->id])
            ->get('/api/public/v1/public-products');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains((string) $fixed->id, $ids);
        $this->assertNotContains((string) $quoteOnly->id, $ids);
    }

    public function test_sort_by_price_puts_quote_only_last_in_both_directions(): void
    {
        $cheap = $this->product(['price' => 10]);
        $expensive = $this->product(['price' => 100]);
        $quoteOnly = $this->product([], true);

        $asc = collect($this->jsonApi()->get('/api/public/v1/public-products?sort=price&filter[brands]=' . $this->brand->id)->json('data'))->pluck('id')->all();
        $this->assertSame([(string) $cheap->id, (string) $expensive->id, (string) $quoteOnly->id], $asc);

        $desc = collect($this->jsonApi()->get('/api/public/v1/public-products?sort=-price&filter[brands]=' . $this->brand->id)->json('data'))->pluck('id')->all();
        $this->assertSame([(string) $expensive->id, (string) $cheap->id, (string) $quoteOnly->id], $desc);
    }

    public function test_quote_only_never_appears_in_on_sale_listing(): void
    {
        $quoteOnly = $this->product([], true);
        \DB::table('products')->where('id', $quoteOnly->id)->update(['is_on_sale' => true]);

        $response = $this->jsonApi()->expects('public-products')
            ->filter(['on_sale' => 'true', 'brands' => (string) $this->brand->id])
            ->get('/api/public/v1/public-products');

        $this->assertNotContains((string) $quoteOnly->id, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_related_products_of_quote_only_skip_the_price_range(): void
    {
        $quoteOnly = $this->product([], true);
        $related = $this->product(['price' => 999]);

        $ids = app(RecommendationEngine::class)->getRelatedProducts($quoteOnly)->pluck('id')->all();

        $this->assertContains($related->id, $ids);
    }
}

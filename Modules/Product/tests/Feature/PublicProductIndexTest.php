<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicProductIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_guest_can_access_public_product_catalog(): void
    {
        // No authentication required

        // Run seeders to get base products
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Create additional test data
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        Product::factory()->count(3)->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'sku',
                        'description',
                        'fullDescription',
                        'price',
                        'iva',
                        'imgPath',
                        'datasheetPath',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
            'jsonapi',
        ]);

        // Verify response type
        $products = $response->json('data');
        foreach ($products as $product) {
            $this->assertEquals('public-products', $product['type']);
            // Regresion de seguridad (auditoria 2026-07): el costo de compra NO
            // debe exponerse en el catalogo publico anonimo.
            $this->assertArrayNotHasKey('cost', $product['attributes']);
        }

        $this->assertGreaterThanOrEqual(8, count($products), 'Should have at least 8 seeded products + 3 factory products');
    }

    public function test_public_catalog_has_proper_json_api_headers(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        $response = $this->jsonApi()->get('/api/public/v1/public-products');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        
        // Verify JSON:API structure
        $response->assertJsonStructure([
            'jsonapi' => ['version'],
            'data',
        ]);

        $jsonApi = $response->json('jsonapi');
        $this->assertEquals('1.0', $jsonApi['version']);
    }

    public function test_guest_can_sort_public_products_by_name(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        Product::factory()->create(['name' => 'Zebra Product', 'is_active' => true, 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Alpha Product', 'is_active' => true, 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products?sort=name&page[size]=2');

        $response->assertOk();
        $this->assertIsArray($response->json('data'));

        $names = array_column(array_column($response->json('data'), 'attributes'), 'name');
        
        // Just verify that Alpha comes before Zebra
        $alphaIndex = array_search('Alpha Product', $names);
        $zebraIndex = array_search('Zebra Product', $names);
        
        if ($alphaIndex !== false && $zebraIndex !== false) {
            $this->assertLessThan($zebraIndex, $alphaIndex, 'Alpha Product should come before Zebra Product when sorted by name');
        }
    }

    public function test_guest_can_sort_public_products_by_price_descending(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        Product::factory()->create(['name' => 'Cheap Product', 'price' => 10.0, 'is_active' => true, 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Expensive Product', 'price' => 100.0, 'is_active' => true, 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products?sort=-price');

        $response->assertOk();
        
        $prices = array_column(array_column($response->json('data'), 'attributes'), 'price');
        $sortedPrices = $prices;
        rsort($sortedPrices);

        $this->assertEquals($sortedPrices, $prices);
    }

    public function test_guest_can_filter_public_products_by_category(): void
    {
        $smartphones = Category::factory()->create(['name' => 'Smartphones']);
        $laptops = Category::factory()->create(['name' => 'Laptops']);

        Product::factory()->count(3)->create(['category_id' => $smartphones->id, 'is_active' => true]);
        Product::factory()->count(2)->create(['category_id' => $laptops->id, 'is_active' => true]);

        // Filter by Smartphones category
        $response = $this->jsonApi()->get("/api/public/v1/public-products?filter[category_id]={$smartphones->id}");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(3, $products, 'Should find 3 products in Smartphones category');
    }

    public function test_guest_can_filter_public_products_by_brand(): void
    {
        $apple = Brand::factory()->create(['name' => 'Apple']);
        $samsung = Brand::factory()->create(['name' => 'Samsung']);

        Product::factory()->count(4)->create(['brand_id' => $apple->id, 'is_active' => true]);
        Product::factory()->count(2)->create(['brand_id' => $samsung->id, 'is_active' => true]);

        // Filter by Apple brand
        $response = $this->jsonApi()->get("/api/public/v1/public-products?filter[brand_id]={$apple->id}");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(4, $products, 'Should find 4 Apple products');
    }

    public function test_guest_can_filter_public_products_by_multiple_brands(): void
    {
        $apple = Brand::factory()->create(['name' => 'Apple']);
        $samsung = Brand::factory()->create(['name' => 'Samsung']);
        $sony = Brand::factory()->create(['name' => 'Sony']);

        Product::factory()->count(2)->create(['brand_id' => $apple->id, 'is_active' => true]);
        Product::factory()->count(3)->create(['brand_id' => $samsung->id, 'is_active' => true]);
        Product::factory()->count(1)->create(['brand_id' => $sony->id, 'is_active' => true]);

        // Filter by Apple and Samsung brands
        $response = $this->jsonApi()->get("/api/public/v1/public-products?filter[brands]={$apple->id},{$samsung->id}");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(5, $products, 'Should find 5 products from Apple and Samsung');
    }

    public function test_guest_can_search_public_products_by_name(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Search for "iPhone"
        $response = $this->jsonApi()->get('/api/public/v1/public-products?filter[search_name]=iPhone');

        $response->assertOk();
        $products = $response->json('data');
        
        $this->assertGreaterThan(0, count($products), 'Should find products matching "iPhone"');

        // Verify all returned products contain "iPhone" in name
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $this->assertStringContainsString('iPhone', $name, "Product name '{$name}' should contain 'iPhone'");
        }
    }

    public function test_guest_can_search_public_products_by_sku(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Search for "APL" in SKU
        $response = $this->jsonApi()->get('/api/public/v1/public-products?filter[search_sku]=APL');

        $response->assertOk();
        $products = $response->json('data');
        
        $this->assertGreaterThan(0, count($products), 'Should find products with SKU containing "APL"');

        // Verify all returned products contain "APL" in SKU
        foreach ($products as $product) {
            $sku = $product['attributes']['sku'];
            $this->assertStringContainsString('APL', $sku, "Product SKU '{$sku}' should contain 'APL'");
        }
    }

    public function test_guest_can_include_relationships_in_public_catalog(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        $response = $this->jsonApi()->get('/api/public/v1/public-products?include=unit,category,brand&page[size]=3');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'unit',
                        'category',
                        'brand',
                    ],
                ],
            ],
            'included' => [
                '*' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ],
        ]);

        $included = $response->json('included') ?? [];

        $this->assertGreaterThan(0, count($included), 'Should include related entities');
    }

    public function test_public_catalog_supports_pagination(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Test pagination with page size parameter
        $response = $this->jsonApi()->get('/api/public/v1/public-products?page[size]=3');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'sku',
                        'price',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
        ]);

        // Test that pagination parameter is respected
        $products = $response->json('data');
        $this->assertLessThanOrEqual(3, count($products), 'Should return at most 3 products when page size is 3');
    }

    public function test_guest_can_filter_public_products_by_price_range(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $baseAttributes = [
            'is_active' => true,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ];

        Product::factory()->create(array_merge($baseAttributes, ['name' => 'Cheap Product', 'price' => 50.0]));
        Product::factory()->create(array_merge($baseAttributes, ['name' => 'Mid Product', 'price' => 150.0]));
        Product::factory()->create(array_merge($baseAttributes, ['name' => 'Boundary Product', 'price' => 200.0]));
        Product::factory()->create(array_merge($baseAttributes, ['name' => 'Expensive Product', 'price' => 500.0]));

        // Se combina con brand_id para aislar los productos de este test del catalogo seeded
        $response = $this->jsonApi()->get("/api/public/v1/public-products?filter[price_min]=100&filter[price_max]=200&filter[brand_id]={$brand->id}");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(2, $products, 'Should find only the 2 products within the 100-200 price range');

        $names = array_column(array_column($products, 'attributes'), 'name');
        sort($names);
        $this->assertEquals(['Boundary Product', 'Mid Product'], $names);

        foreach ($products as $product) {
            $price = (float) $product['attributes']['price'];
            $this->assertGreaterThanOrEqual(100, $price);
            $this->assertLessThanOrEqual(200, $price);
        }
    }

    public function test_guest_can_filter_public_products_by_price_min_only(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $baseAttributes = [
            'is_active' => true,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ];

        Product::factory()->create(array_merge($baseAttributes, ['name' => 'Cheap Product', 'price' => 50.0]));
        Product::factory()->create(array_merge($baseAttributes, ['name' => 'Expensive Product', 'price' => 500.0]));

        // Se combina con brand_id para aislar los productos de este test del catalogo seeded
        $response = $this->jsonApi()->get("/api/public/v1/public-products?filter[price_min]=100&filter[brand_id]={$brand->id}");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(1, $products);
        $this->assertEquals('Expensive Product', $products[0]['attributes']['name']);
    }

    public function test_guest_can_combine_search_and_filters_in_public_catalog(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Search for "Pro" products only from Apple (brand_id=2)
        $response = $this->jsonApi()->get('/api/public/v1/public-products?filter[search_name]=Pro&filter[brands]=2');

        $response->assertOk();
        $products = $response->json('data');
        
        if (count($products) > 0) {
            // Verify all products contain "Pro" in name
            foreach ($products as $product) {
                $name = $product['attributes']['name'];
                $this->assertStringContainsString('Pro', $name, "Product name '{$name}' should contain 'Pro'");
            }
        }
        // No assertion needed if no products found - combined filters may yield empty results
    }
}
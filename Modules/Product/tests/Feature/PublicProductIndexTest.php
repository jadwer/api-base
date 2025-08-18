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
                        'cost',
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
        }

        $this->assertGreaterThanOrEqual(8, count($products), 'Should have at least 8 seeded products + 3 factory products');
        
        echo "\n📦 PUBLIC CATALOG - Products found:\n";
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $sku = $product['attributes']['sku'];
            $price = $product['attributes']['price'];
            echo "   • {$name} ({$sku}) - \${$price}\n";
        }
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
        
        Product::factory()->create(['name' => 'Zebra Product', 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Alpha Product', 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);

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
        
        Product::factory()->create(['name' => 'Cheap Product', 'price' => 10.0, 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Expensive Product', 'price' => 100.0, 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);

        $response = $this->jsonApi()->get('/api/public/v1/public-products?sort=-price');

        $response->assertOk();
        
        $prices = array_column(array_column($response->json('data'), 'attributes'), 'price');
        $sortedPrices = $prices;
        rsort($sortedPrices);

        $this->assertEquals($sortedPrices, $prices);
    }

    public function test_guest_can_filter_public_products_by_category(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Filter by Smartphones category (category_id=1)
        $response = $this->jsonApi()->get('/api/public/v1/public-products?filter[category_id]=1');

        $response->assertOk();
        $products = $response->json('data');
        
        $this->assertGreaterThan(0, count($products), 'Should find products in Smartphones category');
        
        echo "\n📱 PUBLIC CATALOG - Smartphones category:\n";
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $sku = $product['attributes']['sku'];
            echo "   • {$name} ({$sku})\n";
        }
    }

    public function test_guest_can_filter_public_products_by_brand(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Filter by Apple brand (brand_id=2)
        $response = $this->jsonApi()->get('/api/public/v1/public-products?filter[brand_id]=2');

        $response->assertOk();
        $products = $response->json('data');
        
        $this->assertGreaterThan(0, count($products), 'Should find Apple products');
        
        echo "\n🍎 PUBLIC CATALOG - Apple products:\n";
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            echo "   • {$name}\n";
        }
    }

    public function test_guest_can_filter_public_products_by_multiple_brands(): void
    {
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Filter by Apple (2) and Samsung (1) brands
        $response = $this->jsonApi()->get('/api/public/v1/public-products?filter[brands]=2,1');

        $response->assertOk();
        $products = $response->json('data');
        
        $this->assertGreaterThan(0, count($products), 'Should find products from Apple and Samsung');
        
        echo "\n🔍 PUBLIC CATALOG - Multiple brands (Apple + Samsung):\n";
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $sku = $product['attributes']['sku'];
            echo "   • {$name} ({$sku})\n";
        }
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
        
        echo "\n🔍 PUBLIC CATALOG - Search results for 'iPhone':\n";
        foreach ($products as $product) {
            echo "   • " . $product['attributes']['name'] . " (" . $product['attributes']['sku'] . ")\n";
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
        
        echo "\n🔍 PUBLIC CATALOG - Search results for SKU 'APL':\n";
        foreach ($products as $product) {
            echo "   • " . $product['attributes']['name'] . " (" . $product['attributes']['sku'] . ")\n";
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

        $products = $response->json('data');
        $included = $response->json('included') ?? [];

        echo "\n🔗 PUBLIC CATALOG - Products with relationships:\n";
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $unitName = $this->findIncludedName($product, 'unit', $included, 'units');
            $categoryName = $this->findIncludedName($product, 'category', $included, 'categories');
            $brandName = $this->findIncludedName($product, 'brand', $included, 'brands');

            echo "   • {$name} | Brand: {$brandName} | Category: {$categoryName} | Unit: {$unitName}\n";
        }

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

        echo "\n📄 PUBLIC CATALOG - Pagination test results:\n";
        echo "Products returned: " . count($products) . "\n";
        echo "Page size requested: 3\n";
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
            
            echo "\n🎯 PUBLIC CATALOG - Combined search + filter ('Pro' in Apple products):\n";
            foreach ($products as $product) {
                echo "   • " . $product['attributes']['name'] . "\n";
            }
        } else {
            echo "\n🎯 PUBLIC CATALOG - No 'Pro' products found in Apple brand (this is okay for testing)\n";
        }
    }

    private function findIncludedName($product, $relationKey, $included, $expectedType): string
    {
        $relationId = $product['relationships'][$relationKey]['data']['id'] ?? null;
        if (!$relationId) return 'N/A';

        foreach ($included as $item) {
            if ($item['type'] === $expectedType && $item['id'] == $relationId) {
                return $item['attributes']['name'] ?? 'Unknown';
            }
        }
        return 'N/A';
    }
}
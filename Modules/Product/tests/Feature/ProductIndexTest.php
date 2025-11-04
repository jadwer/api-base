<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_list_products(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

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

        $response = $this->jsonApi()->get('/api/v1/products');

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

        // Verify we have products
        $products = $response->json('data');
        $this->assertGreaterThanOrEqual(8, count($products), 'Should have at least 8 seeded products + 3 factory products');
    }

    public function test_admin_can_sort_products_by_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        Product::factory()->create(['name' => 'Zebra Product', 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);
        Product::factory()->create(['name' => 'Alpha Product', 'unit_id' => $unit->id, 'category_id' => $category->id, 'brand_id' => $brand->id]);

        $response = $this->jsonApi()->get('/api/v1/products?sort=name');

        $response->assertOk();
        $this->assertIsArray($response->json('data'));

        $names = array_column($response->json('data'), 'attributes.name');
        $sorted = $names;
        sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);

        $this->assertEquals($sorted, $names);
    }

    public function test_unauthenticated_user_cannot_list_products(): void
    {
        $response = $this->jsonApi()->get('/api/v1/products');

        $response->assertStatus(401);
    }

    public function test_customer_can_list_products(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/products');

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
                    ],
                ],
            ],
        ]);
    }

    public function test_seeded_products_are_available(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Run seeders to ensure products exist
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        $response = $this->jsonApi()->get('/api/v1/products?include=unit,category,brand');

        $response->assertOk();

        $products = $response->json('data');
        $included = $response->json('included') ?? [];

        // Assert we have the expected seeded products
        $productNames = array_column(array_column($products, 'attributes'), 'name');
        $this->assertContains('iPhone 15 Pro', $productNames);
        $this->assertContains('Samsung Galaxy S24 Ultra', $productNames);
        $this->assertContains('MacBook Pro 14" M3', $productNames);
    }

    public function test_admin_can_search_products_by_partial_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Run seeders to get products with known names
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Search for "iPhone" - should match "iPhone 15 Pro"
        $response = $this->jsonApi()->get('/api/v1/products?filter[search_name]=iPhone');

        $response->assertOk();
        $products = $response->json('data');
        
        $this->assertGreaterThan(0, count($products), 'Should find products matching "iPhone"');
        
        // Verify all returned products contain "iPhone" in name
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $this->assertStringContainsString('iPhone', $name, "Product name '{$name}' should contain 'iPhone'");
        }
    }

    public function test_admin_can_search_products_by_partial_sku(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Run seeders to get products with known SKUs
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Search for "APL" - should match SKUs containing APL (like APL-IPH15P-256)
        $response = $this->jsonApi()->get('/api/v1/products?filter[search_sku]=APL');

        $response->assertOk();
        $products = $response->json('data');
        
        $this->assertGreaterThan(0, count($products), 'Should find products with SKU containing "APL"');
        
        // Verify all returned products contain "APL" in SKU
        foreach ($products as $product) {
            $sku = $product['attributes']['sku'];
            $this->assertStringContainsString('APL', $sku, "Product SKU '{$sku}' should contain 'APL'");
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

    public function test_products_have_pagination(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Run seeders to get products
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Test pagination
        $response = $this->jsonApi()->get('/api/v1/products?page[number]=1&page[size]=5');

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
            'meta' => [
                'page' => [
                    'currentPage',
                    'from',
                    'lastPage',
                    'perPage',
                    'to',
                    'total',
                ],
            ],
            'links' => [
                'first',
                'last',
            ],
        ]);

        // Verify pagination meta
        $meta = $response->json('meta.page');
        $this->assertEquals(1, $meta['currentPage']);
        $this->assertEquals(5, $meta['perPage']);
        $this->assertArrayHasKey('total', $meta);
        $this->assertArrayHasKey('links', $response->json());
    }

    public function test_admin_can_filter_products_by_multiple_brands(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create test brands and products
        $apple = Brand::factory()->create(['name' => 'Apple']);
        $samsung = Brand::factory()->create(['name' => 'Samsung']);
        $sony = Brand::factory()->create(['name' => 'Sony']);

        Product::factory()->count(2)->create(['brand_id' => $apple->id]);
        Product::factory()->count(2)->create(['brand_id' => $samsung->id]);
        Product::factory()->count(1)->create(['brand_id' => $sony->id]);

        // Filter by Apple and Samsung brands
        $response = $this->jsonApi()->get("/api/v1/products?filter[brands]={$apple->id},{$samsung->id}&include=brand");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(4, $products, 'Should find 4 products from Apple and Samsung');

        // Verify products are only from Apple or Samsung
        $brandIds = collect($products)->pluck('relationships.brand.data.id')->unique();
        $this->assertCount(2, $brandIds, 'Should only have products from 2 brands');
        $this->assertTrue($brandIds->contains((string)$apple->id), 'Should have Apple products');
        $this->assertTrue($brandIds->contains((string)$samsung->id), 'Should have Samsung products');
    }

    public function test_admin_can_filter_products_by_single_brand(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create test brands and products
        $apple = Brand::factory()->create(['name' => 'Apple']);
        $samsung = Brand::factory()->create(['name' => 'Samsung']);

        Product::factory()->count(3)->create(['brand_id' => $apple->id]);
        Product::factory()->count(2)->create(['brand_id' => $samsung->id]);

        // Filter by Apple only
        $response = $this->jsonApi()->get("/api/v1/products?filter[brand_id]={$apple->id}&include=brand");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(3, $products, 'Should find 3 Apple products');

        // Verify all products are from Apple
        foreach ($products as $product) {
            $brandId = $product['relationships']['brand']['data']['id'] ?? null;
            $this->assertEquals((string)$apple->id, $brandId, 'All products should be from Apple');
        }
    }

    public function test_admin_can_combine_search_and_brand_filters(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create test brands and products
        $apple = Brand::factory()->create(['name' => 'Apple']);
        $samsung = Brand::factory()->create(['name' => 'Samsung']);

        Product::factory()->create(['name' => 'iPhone Pro', 'brand_id' => $apple->id]);
        Product::factory()->create(['name' => 'MacBook Pro', 'brand_id' => $apple->id]);
        Product::factory()->create(['name' => 'iPad Air', 'brand_id' => $apple->id]);
        Product::factory()->create(['name' => 'Galaxy Pro', 'brand_id' => $samsung->id]);

        // Search for "Pro" products only from Apple
        $response = $this->jsonApi()->get("/api/v1/products?filter[search_name]=Pro&filter[brands]={$apple->id}&include=brand");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(2, $products, 'Should find 2 Apple products with "Pro" in name');

        // Verify all products contain "Pro" and are from Apple
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $brandId = $product['relationships']['brand']['data']['id'] ?? null;
            $this->assertStringContainsString('Pro', $name, "Product name should contain 'Pro'");
            $this->assertEquals((string)$apple->id, $brandId, 'Product should be from Apple');
        }
    }

    public function test_admin_can_search_across_multiple_brands(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create test brands and products
        $apple = Brand::factory()->create(['name' => 'Apple']);
        $samsung = Brand::factory()->create(['name' => 'Samsung']);
        $sony = Brand::factory()->create(['name' => 'Sony']);

        Product::factory()->create(['name' => 'Galaxy Ultra', 'brand_id' => $samsung->id]);
        Product::factory()->create(['name' => 'Ultra Pro Phone', 'brand_id' => $samsung->id]);
        Product::factory()->create(['name' => 'iPhone 14', 'brand_id' => $apple->id]);
        Product::factory()->create(['name' => 'Ultra TV', 'brand_id' => $sony->id]);

        // Search for "Ultra" products from Samsung and Apple only
        $response = $this->jsonApi()->get("/api/v1/products?filter[search_name]=Ultra&filter[brands]={$samsung->id},{$apple->id}&include=brand");

        $response->assertOk();
        $products = $response->json('data');

        $this->assertCount(2, $products, 'Should find 2 Samsung products with "Ultra" in name');

        // Verify all products contain "Ultra" and are from Samsung or Apple
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $brandId = $product['relationships']['brand']['data']['id'] ?? null;
            $this->assertStringContainsString('Ultra', $name, "Product name should contain 'Ultra'");
            $this->assertTrue(
                $brandId === (string)$samsung->id || $brandId === (string)$apple->id,
                'Product should be from Samsung or Apple'
            );
        }
    }
}

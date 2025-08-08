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

        // Display existing products for debugging/info
        $products = $response->json('data');
        $this->assertGreaterThanOrEqual(8, count($products), 'Should have at least 8 seeded products + 3 factory products');
        
        // Display seeded products info
        echo "\n📦 Products found in test:\n";
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $sku = $product['attributes']['sku'];
            $price = $product['attributes']['price'];
            echo "   • {$name} ({$sku}) - \${$price}\n";
        }
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

        echo "\n🏪 SEEDED PRODUCTS CATALOG:\n";
        echo "=" . str_repeat("=", 80) . "\n";
        
        foreach ($products as $product) {
            $name = $product['attributes']['name'];
            $sku = $product['attributes']['sku'];
            $price = $product['attributes']['price'];
            $description = $product['attributes']['description'];
            
            // Find related data in included
            $unitName = $this->findIncludedName($product, 'unit', $included, 'units');
            $categoryName = $this->findIncludedName($product, 'category', $included, 'categories');
            $brandName = $this->findIncludedName($product, 'brand', $included, 'brands');

            echo "📱 {$name}\n";
            echo "   SKU: {$sku}\n";
            echo "   Price: \${$price}\n";
            echo "   Brand: {$brandName} | Category: {$categoryName} | Unit: {$unitName}\n";
            echo "   Description: {$description}\n";
            echo "   " . str_repeat("-", 75) . "\n";
        }
        
        // Assert we have the expected seeded products
        $productNames = array_column(array_column($products, 'attributes'), 'name');
        $this->assertContains('iPhone 15 Pro', $productNames);
        $this->assertContains('Samsung Galaxy S24 Ultra', $productNames);
        $this->assertContains('MacBook Pro 14" M3', $productNames);
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

        echo "\n📄 PAGINATION TEST RESULTS:\n";
        echo "Current Page: {$meta['currentPage']}\n";
        echo "Per Page: {$meta['perPage']}\n";
        echo "Total: {$meta['total']}\n";
        echo "Last Page: {$meta['lastPage']}\n";
    }
}

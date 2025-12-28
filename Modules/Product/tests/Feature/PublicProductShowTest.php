<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicProductShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_guest_can_view_single_public_product(): void
    {
        // No authentication required

        // Create test data
        $unit = Unit::factory()->create(['name' => 'Unit']);
        $category = Category::factory()->create(['name' => 'Test Category']);
        $brand = Brand::factory()->create(['name' => 'Test Brand']);
        
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'description' => 'Test description',
            'price' => 99.99,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
                'relationships' => [
                    'unit',
                    'category',
                    'brand',
                ],
            ],
            'jsonapi',
        ]);

        // Verify data
        $productData = $response->json('data');
        $this->assertEquals('public-products', $productData['type']);
        $this->assertEquals($product->id, $productData['id']);
        $this->assertEquals('Test Product', $productData['attributes']['name']);
        $this->assertEquals('TEST-001', $productData['attributes']['sku']);
        $this->assertEquals(99.99, $productData['attributes']['price']);
    }

    public function test_guest_can_view_public_product_with_relationships(): void
    {
        // Create test data
        $unit = Unit::factory()->create(['name' => 'Pieces']);
        $category = Category::factory()->create(['name' => 'Electronics', 'description' => 'Electronic devices']);
        $brand = Brand::factory()->create(['name' => 'TechBrand', 'description' => 'Technology brand']);
        
        $product = Product::factory()->create([
            'name' => 'Smartphone Pro',
            'sku' => 'SMART-PRO-001',
            'description' => 'Latest smartphone',
            'full_description' => 'Latest smartphone with advanced features',
            'price' => 999.99,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}?include=unit,category,brand");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'unit' => ['data' => ['id', 'type']],
                    'category' => ['data' => ['id', 'type']],
                    'brand' => ['data' => ['id', 'type']],
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

        $included = $response->json('included');

        // Verify relationships are included
        $this->assertNotEmpty($included, 'Should include related entities');

        $this->assertNotNull(collect($included)->firstWhere('type', 'units'), 'Should include unit data');
        $this->assertNotNull(collect($included)->firstWhere('type', 'categories'), 'Should include category data');
        $this->assertNotNull(collect($included)->firstWhere('type', 'brands'), 'Should include brand data');
    }

    public function test_guest_receives_404_for_nonexistent_product(): void
    {
        $response = $this->jsonApi()->get('/api/public/v1/public-products/999999');

        $response->assertNotFound();
    }

    public function test_public_product_show_has_proper_json_api_headers(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}");

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

    public function test_guest_can_view_seeded_product(): void
    {
        // Run seeders to get seeded products
        $this->artisan('db:seed', ['--class' => 'Modules\\Product\\Database\\Seeders\\ProductDatabaseSeeder']);

        // Get first seeded product
        $product = Product::first();
        $this->assertNotNull($product, 'Should have at least one seeded product');

        $response = $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}?include=unit,category,brand");

        $response->assertOk();
        
        $productData = $response->json('data');

        $this->assertNotNull($productData['attributes']['name']);
        $this->assertNotNull($productData['attributes']['sku']);
    }

    public function test_public_product_attributes_are_complete(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'name' => 'Complete Product',
            'sku' => 'COMPLETE-001',
            'description' => 'Short description',
            'full_description' => 'Long detailed description of the product',
            'price' => 149.99,
            'cost' => 100.00,
            'iva' => true,
            'img_path' => '/images/product.jpg',
            'datasheet_path' => '/docs/product.pdf',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response = $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}");

        $response->assertOk();
        
        $attributes = $response->json('data.attributes');

        // Verify all expected attributes are present
        $this->assertEquals('Complete Product', $attributes['name']);
        $this->assertEquals('COMPLETE-001', $attributes['sku']);
        $this->assertEquals('Short description', $attributes['description']);
        $this->assertEquals('Long detailed description of the product', $attributes['fullDescription']);
        $this->assertEquals(149.99, $attributes['price']);
        $this->assertEquals(100.00, $attributes['cost']);
        $this->assertTrue($attributes['iva']);
        $this->assertEquals('/images/product.jpg', $attributes['imgPath']);
        $this->assertEquals('/docs/product.pdf', $attributes['datasheetPath']);
        $this->assertNotNull($attributes['createdAt']);
        $this->assertNotNull($attributes['updatedAt']);
    }

    public function test_public_product_relationship_links_are_accessible(): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $product = Product::factory()->create([
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        // Test unit relationship
        $response = $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}/unit");
        $response->assertOk();

        // Test category relationship
        $response = $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}/category");
        $response->assertOk();

        // Test brand relationship
        $response = $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}/brand");
        $response->assertOk();
    }
}
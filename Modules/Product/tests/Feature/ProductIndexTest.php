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

        // Create test data
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
}

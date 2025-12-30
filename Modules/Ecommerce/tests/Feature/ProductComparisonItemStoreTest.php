<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\ProductComparison;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductComparisonItemStoreTest extends TestCase
{
    public function test_admin_can_create_comparison_item()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'productId' => $product->id,
                'position' => 0,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'product-comparison-items',
                'attributes' => [
                    'comparisonId' => $comparison->id,
                    'productId' => $product->id,
                    'position' => 0,
                ],
            ],
        ]);

        $this->assertDatabaseHas('product_comparison_items', [
            'comparison_id' => $comparison->id,
            'product_id' => $product->id,
            'position' => 0,
        ]);
    }

    public function test_customer_can_add_item_to_their_own_comparison()
    {
        $customer = User::role('customer')->first();
        $comparison = ProductComparison::factory()->create(['user_id' => $customer->id]);
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'productId' => $product->id,
                'position' => 1,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertCreated();
    }

    public function test_customer_cannot_add_item_to_other_users_comparison()
    {
        $customer = User::role('customer')->first();
        $otherUser = User::factory()->create();
        $comparison = ProductComparison::factory()->create(['user_id' => $otherUser->id]);
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'productId' => $product->id,
                'position' => 0,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertStatus(403);
    }

    public function test_tech_user_cannot_create_comparison_item()
    {
        $tech = User::role('tech')->first();
        $comparison = ProductComparison::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'productId' => $product->id,
                'position' => 0,
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_comparison_item()
    {
        $comparison = ProductComparison::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'productId' => $product->id,
                'position' => 0,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertStatus(401);
    }

    public function test_comparison_id_is_required()
    {
        $admin = User::role('admin')->first();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'productId' => $product->id,
                'position' => 0,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/comparisonId']
        ]);
    }

    public function test_product_id_is_required()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'position' => 0,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/productId']
        ]);
    }

    public function test_position_defaults_to_zero()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'productId' => $product->id,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'position' => 0,
                ],
            ],
        ]);
    }

    public function test_position_must_be_integer()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'productId' => $product->id,
                'position' => 'not-a-number',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/position']
        ]);
    }

    public function test_position_cannot_be_negative()
    {
        $admin = User::role('admin')->first();
        $comparison = ProductComparison::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-comparison-items',
            'attributes' => [
                'comparisonId' => $comparison->id,
                'productId' => $product->id,
                'position' => -1,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-comparison-items')
            ->withData($data)
            ->post('/api/v1/product-comparison-items');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/position']
        ]);
    }
}

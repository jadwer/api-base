<?php

namespace Modules\Ecommerce\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductRecommendationEndpointsTest extends TestCase
{
    /** @test */
    public function public_can_access_related_products_endpoint()
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'categoryId' => $category->id,
            'price' => 100.00,
            'isActive' => true,
        ]);

        Product::factory()->create([
            'categoryId' => $category->id,
            'price' => 110.00,
            'isActive' => true,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}/related");

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes',
                ],
            ],
            'meta' => [
                'count',
                'type',
            ],
        ]);
        $response->assertJson([
            'meta' => [
                'type' => 'related',
            ],
        ]);
    }

    /** @test */
    public function related_products_respects_limit_parameter()
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'categoryId' => $category->id,
            'price' => 100.00,
            'isActive' => true,
        ]);

        Product::factory()->count(10)->create([
            'categoryId' => $category->id,
            'price' => 100.00,
            'isActive' => true,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}/related?limit=3");

        $response->assertSuccessful();
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function public_can_access_frequently_bought_together_endpoint()
    {
        $customer = User::factory()->create();

        $product = Product::factory()->create(['isActive' => true]);
        $relatedProduct = Product::factory()->create(['isActive' => true]);

        $order = SalesOrder::factory()->create(['customerId' => $customer->id]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'productId' => $product->id,
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'productId' => $relatedProduct->id,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}/frequently-bought-together");

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'type',
            ],
        ]);
        $response->assertJson([
            'meta' => [
                'type' => 'frequently_bought_together',
            ],
        ]);
    }

    /** @test */
    public function public_can_access_trending_products_endpoint()
    {
        $customer = User::factory()->create();

        $product = Product::factory()->create(['isActive' => true]);

        // Create recent sales
        $order = SalesOrder::factory()->create([
            'customerId' => $customer->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'productId' => $product->id,
        ]);

        $response = $this->getJson('/api/v1/products/trending');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'type',
            ],
        ]);
        $response->assertJson([
            'meta' => [
                'type' => 'trending',
            ],
        ]);
    }

    /** @test */
    public function public_can_access_popular_products_endpoint()
    {
        Product::factory()->create([
            'isActive' => true,
            'average_rating' => 4.5,
            'total_reviews' => 10,
        ]);

        $response = $this->getJson('/api/v1/products/popular');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'type',
            ],
        ]);
        $response->assertJson([
            'meta' => [
                'type' => 'popular',
            ],
        ]);
    }

    /** @test */
    public function public_can_access_new_arrivals_endpoint()
    {
        Product::factory()->create([
            'isActive' => true,
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v1/products/new-arrivals');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'type',
            ],
        ]);
        $response->assertJson([
            'meta' => [
                'type' => 'new_arrivals',
            ],
        ]);
    }

    /** @test */
    public function authenticated_user_can_access_personalized_recommendations()
    {
        $customer = User::role('customer')->first();

        $category = Category::factory()->create();
        $purchasedProduct = Product::factory()->create([
            'categoryId' => $category->id,
            'isActive' => true,
        ]);

        $order = SalesOrder::factory()->create(['customerId' => $customer->id]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'productId' => $purchasedProduct->id,
        ]);

        Product::factory()->create([
            'categoryId' => $category->id,
            'isActive' => true,
            'average_rating' => 4.5,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/products/recommended');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'type',
            ],
        ]);
        $response->assertJson([
            'meta' => [
                'type' => 'personalized',
            ],
        ]);
    }

    /** @test */
    public function guest_cannot_access_personalized_recommendations()
    {
        $response = $this->getJson('/api/v1/products/recommended');

        $response->assertStatus(401);
    }

    /** @test */
    public function all_endpoints_respect_limit_parameter()
    {
        $category = Category::factory()->create();
        Product::factory()->count(20)->create([
            'categoryId' => $category->id,
            'isActive' => true,
            'average_rating' => 4.5,
            'total_reviews' => 10,
            'price' => 100.00,
        ]);

        // Test trending
        $response = $this->getJson('/api/v1/products/trending?limit=5');
        $response->assertSuccessful();
        $this->assertLessThanOrEqual(5, count($response->json('data')));

        // Test popular
        $response = $this->getJson('/api/v1/products/popular?limit=5');
        $response->assertSuccessful();
        $this->assertLessThanOrEqual(5, count($response->json('data')));

        // Test new arrivals
        $response = $this->getJson('/api/v1/products/new-arrivals?limit=5');
        $response->assertSuccessful();
        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    /** @test */
    public function returns_404_for_non_existent_product()
    {
        $response = $this->getJson('/api/v1/products/99999/related');

        $response->assertStatus(404);
    }

    /** @test */
    public function all_endpoints_return_empty_array_when_no_results()
    {
        $product = Product::factory()->create(['isActive' => true]);

        // No related products
        $response = $this->getJson("/api/v1/products/{$product->id}/related");
        $response->assertSuccessful();
        $response->assertJson(['data' => []]);
        $response->assertJson(['meta' => ['count' => 0]]);

        // No frequently bought together
        $response = $this->getJson("/api/v1/products/{$product->id}/frequently-bought-together");
        $response->assertSuccessful();
        $response->assertJson(['data' => []]);
        $response->assertJson(['meta' => ['count' => 0]]);
    }

    /** @test */
    public function endpoints_only_return_active_products()
    {
        $category = Category::factory()->create();

        $activeProduct = Product::factory()->create([
            'categoryId' => $category->id,
            'isActive' => true,
            'average_rating' => 4.5,
            'total_reviews' => 10,
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $inactiveProduct = Product::factory()->create([
            'categoryId' => $category->id,
            'isActive' => false,
            'average_rating' => 4.8,
            'total_reviews' => 15,
            'created_at' => Carbon::now(),
        ]);

        // Test popular
        $response = $this->getJson('/api/v1/products/popular');
        $response->assertSuccessful();
        $productIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($activeProduct->id, $productIds);
        $this->assertNotContains($inactiveProduct->id, $productIds);

        // Test new arrivals
        $response = $this->getJson('/api/v1/products/new-arrivals');
        $response->assertSuccessful();
        $productIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($activeProduct->id, $productIds);
        $this->assertNotContains($inactiveProduct->id, $productIds);
    }
}

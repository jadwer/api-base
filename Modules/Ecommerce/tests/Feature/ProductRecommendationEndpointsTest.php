<?php

namespace Modules\Ecommerce\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\User\Models\User;
use Tests\TestCase;

class ProductRecommendationEndpointsTest extends TestCase
{
    public function test_public_can_access_related_products_endpoint()
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 110.00,
            'is_active' => true,
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

    public function test_related_products_respects_limit_parameter()
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'is_active' => true,
        ]);

        Product::factory()->count(10)->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}/related?limit=3");

        $response->assertSuccessful();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_public_can_access_frequently_bought_together_endpoint()
    {
        $contact = Contact::factory()->create(['is_customer' => true]);

        $product = Product::factory()->create(['is_active' => true]);
        $relatedProduct = Product::factory()->create(['is_active' => true]);

        $order = SalesOrder::factory()->create(['contact_id' => $contact->id]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $relatedProduct->id,
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

    public function test_public_can_access_trending_products_endpoint()
    {
        $contact = Contact::factory()->create(['is_customer' => true]);

        $product = Product::factory()->create(['is_active' => true]);

        // Create recent sales
        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
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

    public function test_public_can_access_popular_products_endpoint()
    {
        Product::factory()->create([
            'is_active' => true,
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

    public function test_public_can_access_new_arrivals_endpoint()
    {
        Product::factory()->create([
            'is_active' => true,
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

    public function test_authenticated_user_can_access_personalized_recommendations()
    {
        $customer = User::role('customer')->first();
        $contact = Contact::factory()->create(['is_customer' => true]);

        $category = Category::factory()->create();
        $purchasedProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $order = SalesOrder::factory()->create(['contact_id' => $contact->id]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $purchasedProduct->id,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
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

    public function test_guest_cannot_access_personalized_recommendations()
    {
        $response = $this->getJson('/api/v1/products/recommended');

        $response->assertStatus(401);
    }

    public function test_all_endpoints_respect_limit_parameter()
    {
        $category = Category::factory()->create();
        Product::factory()->count(20)->create([
            'category_id' => $category->id,
            'is_active' => true,
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

    public function test_returns_404_for_non_existent_product()
    {
        $response = $this->getJson('/api/v1/products/99999/related');

        $response->assertStatus(404);
    }

    public function test_all_endpoints_return_empty_array_when_no_results()
    {
        $product = Product::factory()->create(['is_active' => true]);

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

    public function test_endpoints_only_return_active_products()
    {
        // Deactivate existing products to ensure test isolation (can't delete due to FK constraints)
        Product::query()->update(['is_active' => false]);

        $category = Category::factory()->create();

        $activeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'average_rating' => 4.5,
            'total_reviews' => 10,
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
            'average_rating' => 4.8,
            'total_reviews' => 15,
            'created_at' => Carbon::now(),
        ]);

        // Test popular (JSON:API IDs are strings)
        $response = $this->getJson('/api/v1/products/popular');
        $response->assertSuccessful();
        $productIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $activeProduct->id, $productIds);
        $this->assertNotContains((string) $inactiveProduct->id, $productIds);

        // Test new arrivals
        $response = $this->getJson('/api/v1/products/new-arrivals');
        $response->assertSuccessful();
        $productIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string) $activeProduct->id, $productIds);
        $this->assertNotContains((string) $inactiveProduct->id, $productIds);
    }
}

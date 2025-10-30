<?php

namespace Modules\Ecommerce\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Ecommerce\Services\RecommendationEngine;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\User\Models\User;
use Tests\TestCase;

class RecommendationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected RecommendationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->engine = new RecommendationEngine();
    }

    /** @test */
    public function related_products_returns_same_category_products()
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $baseProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 100.00,
            'is_active' => true,
        ]);

        // Create related products in same category
        $relatedProduct1 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 90.00, // Within ±30%
            'is_active' => true,
            'average_rating' => 4.5,
            'total_sales' => 100,
        ]);

        $relatedProduct2 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 110.00, // Within ±30%
            'is_active' => true,
            'average_rating' => 4.8,
            'total_sales' => 150,
        ]);

        // Create unrelated products (different category, out of price range)
        Product::factory()->create([
            'price' => 200.00, // Outside ±30%
            'is_active' => true,
        ]);

        $related = $this->engine->getRelatedProducts($baseProduct, 6);

        $this->assertCount(2, $related);
        $this->assertTrue($related->contains('id', $relatedProduct1->id));
        $this->assertTrue($related->contains('id', $relatedProduct2->id));
    }

    /** @test */
    public function related_products_filters_by_price_range()
    {
        $category = Category::factory()->create();

        $baseProduct = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'is_active' => true,
        ]);

        // Within range (70-130)
        $inRangeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 120.00,
            'is_active' => true,
        ]);

        // Outside range
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 200.00, // Outside ±30%
            'is_active' => true,
        ]);

        $related = $this->engine->getRelatedProducts($baseProduct, 6);

        $this->assertCount(1, $related);
        $this->assertEquals($inRangeProduct->id, $related->first()->id);
    }

    /** @test */
    public function frequently_bought_together_uses_order_data()
    {
        $customer = User::factory()->create();

        $baseProduct = Product::factory()->create();
        $productA = Product::factory()->create(['is_active' => true]);
        $productB = Product::factory()->create(['is_active' => true]);
        $productC = Product::factory()->create(['is_active' => true]);

        // Create 3 orders where baseProduct is bought with productA
        for ($i = 0; $i < 3; $i++) {
            $order = SalesOrder::factory()->create(['customer_id' => $customer->id]);
            SalesOrderItem::factory()->create([
                'sales_order_id' => $order->id,
                'product_id' => $baseProduct->id,
            ]);
            SalesOrderItem::factory()->create([
                'sales_order_id' => $order->id,
                'product_id' => $productA->id,
            ]);
        }

        // Create 2 orders where baseProduct is bought with productB
        for ($i = 0; $i < 2; $i++) {
            $order = SalesOrder::factory()->create(['customer_id' => $customer->id]);
            SalesOrderItem::factory()->create([
                'sales_order_id' => $order->id,
                'product_id' => $baseProduct->id,
            ]);
            SalesOrderItem::factory()->create([
                'sales_order_id' => $order->id,
                'product_id' => $productB->id,
            ]);
        }

        // ProductC is never bought with baseProduct
        $order = SalesOrder::factory()->create(['customer_id' => $customer->id]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $productC->id,
        ]);

        $result = $this->engine->getFrequentlyBoughtTogether($baseProduct, 4);

        $this->assertCount(2, $result);
        // ProductA should be first (frequency: 3)
        $this->assertEquals($productA->id, $result->first()->id);
        // ProductB should be second (frequency: 2)
        $this->assertEquals($productB->id, $result->get(1)->id);
    }

    /** @test */
    public function personalized_recommendations_based_on_purchase_history()
    {
        $customer = User::role('customer')->first();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        // Customer has purchased products from category1
        $purchasedProduct = Product::factory()->create([
            'category_id' => $category1->id,
            'is_active' => true,
        ]);

        $order = SalesOrder::factory()->create(['customer_id' => $customer->id]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $purchasedProduct->id,
        ]);

        // Create more products in category1 (not purchased yet)
        $recommendedProduct = Product::factory()->create([
            'category_id' => $category1->id,
            'is_active' => true,
            'average_rating' => 4.5,
        ]);

        // Create products in category2 (customer has no history here)
        Product::factory()->create([
            'category_id' => $category2->id,
            'is_active' => true,
            'average_rating' => 4.8,
        ]);

        $result = $this->engine->getPersonalizedRecommendations($customer, 12);

        // Should recommend products from category1, excluding already purchased
        $this->assertTrue($result->contains('id', $recommendedProduct->id));
        $this->assertFalse($result->contains('id', $purchasedProduct->id));
    }

    /** @test */
    public function personalized_recommendations_falls_back_to_popular_if_no_history()
    {
        $customer = User::factory()->create();

        // Create popular products (no purchase history for this customer)
        Product::factory()->create([
            'is_active' => true,
            'average_rating' => 4.5,
            'total_reviews' => 10,
        ]);

        $result = $this->engine->getPersonalizedRecommendations($customer, 12);

        $this->assertNotEmpty($result);
    }

    /** @test */
    public function trending_products_returns_most_sold_in_last_30_days()
    {
        $customer = User::factory()->create();

        $trendingProduct = Product::factory()->create(['is_active' => true]);
        $oldProduct = Product::factory()->create(['is_active' => true]);

        // Create recent sales for trending product (within 30 days)
        for ($i = 0; $i < 5; $i++) {
            $order = SalesOrder::factory()->create([
                'customer_id' => $customer->id,
                'created_at' => Carbon::now()->subDays(10),
            ]);
            SalesOrderItem::factory()->create([
                'sales_order_id' => $order->id,
                'product_id' => $trendingProduct->id,
            ]);
        }

        // Create old sales (more than 30 days ago)
        $oldOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => Carbon::now()->subDays(45),
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $oldOrder->id,
            'product_id' => $oldProduct->id,
        ]);

        $result = $this->engine->getTrendingProducts(12);

        $this->assertNotEmpty($result);
        $this->assertTrue($result->contains('id', $trendingProduct->id));
        $this->assertFalse($result->contains('id', $oldProduct->id));
    }

    /** @test */
    public function popular_products_requires_minimum_rating_and_reviews()
    {
        // Product with high rating and enough reviews
        $popularProduct = Product::factory()->create([
            'is_active' => true,
            'average_rating' => 4.5,
            'total_reviews' => 10,
        ]);

        // Product with high rating but not enough reviews
        Product::factory()->create([
            'is_active' => true,
            'average_rating' => 4.8,
            'total_reviews' => 3, // Less than 5
        ]);

        // Product with enough reviews but low rating
        Product::factory()->create([
            'is_active' => true,
            'average_rating' => 3.5, // Less than 4.0
            'total_reviews' => 10,
        ]);

        $result = $this->engine->getPopularProducts(12);

        $this->assertCount(1, $result);
        $this->assertEquals($popularProduct->id, $result->first()->id);
    }

    /** @test */
    public function popular_products_orders_by_rating_then_reviews()
    {
        $product1 = Product::factory()->create([
            'is_active' => true,
            'average_rating' => 4.8,
            'total_reviews' => 20,
        ]);

        $product2 = Product::factory()->create([
            'is_active' => true,
            'average_rating' => 4.5,
            'total_reviews' => 50,
        ]);

        $product3 = Product::factory()->create([
            'is_active' => true,
            'average_rating' => 4.8,
            'total_reviews' => 30,
        ]);

        $result = $this->engine->getPopularProducts(12);

        // Should order by rating DESC, then total_reviews DESC
        $this->assertEquals($product3->id, $result->get(0)->id); // 4.8 rating, 30 reviews
        $this->assertEquals($product1->id, $result->get(1)->id); // 4.8 rating, 20 reviews
        $this->assertEquals($product2->id, $result->get(2)->id); // 4.5 rating, 50 reviews
    }

    /** @test */
    public function new_arrivals_returns_recently_created_products()
    {
        // Create old product
        $oldProduct = Product::factory()->create([
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(30),
        ]);

        // Create new products
        $newProduct1 = Product::factory()->create([
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $newProduct2 = Product::factory()->create([
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $result = $this->engine->getNewArrivals(12);

        $this->assertNotEmpty($result);
        // Most recent should be first
        $this->assertEquals($newProduct2->id, $result->first()->id);
        $this->assertEquals($newProduct1->id, $result->get(1)->id);
    }

    /** @test */
    public function new_arrivals_respects_limit()
    {
        // Create 15 products
        Product::factory()->count(15)->create(['is_active' => true]);

        $result = $this->engine->getNewArrivals(5);

        $this->assertCount(5, $result);
    }

    /** @test */
    public function all_recommendation_methods_exclude_inactive_products()
    {
        $category = Category::factory()->create();

        $activeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'average_rating' => 4.5,
            'total_reviews' => 10,
        ]);

        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
            'average_rating' => 4.8,
            'total_reviews' => 15,
        ]);

        // Test related products
        $related = $this->engine->getRelatedProducts($activeProduct, 6);
        $this->assertFalse($related->contains('id', $inactiveProduct->id));

        // Test popular products
        $popular = $this->engine->getPopularProducts(12);
        $this->assertFalse($popular->contains('id', $inactiveProduct->id));

        // Test new arrivals
        $newArrivals = $this->engine->getNewArrivals(12);
        $this->assertFalse($newArrivals->contains('id', $inactiveProduct->id));
    }
}

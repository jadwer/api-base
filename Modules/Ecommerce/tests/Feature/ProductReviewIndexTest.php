<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Product\Models\Product;
use App\Models\User;

class ProductReviewIndexTest extends TestCase
{
    /**
     * Test guest can view approved product reviews.
     */
    public function test_guest_can_view_approved_product_reviews(): void
    {
        // Create products and users
        $product = Product::factory()->create();
        $user = User::factory()->create();

        // Create mix of approved and pending reviews
        ProductReview::factory()->count(3)->approved()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        ProductReview::factory()->count(2)->pending()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        // Guest should only see approved reviews (indexQuery filters by status)
        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test admin can view all product reviews.
     */
    public function test_admin_can_view_all_product_reviews(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        ProductReview::factory()->count(3)->approved()->create([
            'product_id' => $product->id,
        ]);

        ProductReview::factory()->count(2)->pending()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test can filter product reviews by product.
     */
    public function test_can_filter_product_reviews_by_product(): void
    {
        $admin = $this->getAdminUser();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        ProductReview::factory()->count(3)->approved()->create([
            'product_id' => $product1->id,
        ]);

        ProductReview::factory()->count(2)->approved()->create([
            'product_id' => $product2->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->filter(['productId' => $product1->id])
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test can filter product reviews by status.
     */
    public function test_can_filter_product_reviews_by_status(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        ProductReview::factory()->count(3)->approved()->create([
            'product_id' => $product->id,
        ]);

        ProductReview::factory()->count(2)->pending()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->filter(['status' => 'approved'])
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test can filter product reviews by rating.
     */
    public function test_can_filter_product_reviews_by_rating(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        ProductReview::factory()->count(3)->rating(5)->approved()->create([
            'product_id' => $product->id,
        ]);

        ProductReview::factory()->count(2)->rating(3)->approved()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->filter(['rating' => '5'])
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test can filter product reviews by verified purchase.
     */
    public function test_can_filter_product_reviews_by_verified_purchase(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        ProductReview::factory()->count(3)->verifiedPurchase()->approved()->create([
            'product_id' => $product->id,
        ]);

        ProductReview::factory()->count(2)->approved()->create([
            'product_id' => $product->id,
            'is_verified_purchase' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->filter(['isVerifiedPurchase' => '1'])
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test can sort product reviews by rating.
     */
    public function test_can_sort_product_reviews_by_rating(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $review1 = ProductReview::factory()->rating(5)->approved()->create(['product_id' => $product->id]);
        $review2 = ProductReview::factory()->rating(3)->approved()->create(['product_id' => $product->id]);
        $review3 = ProductReview::factory()->rating(4)->approved()->create(['product_id' => $product->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->sort('rating')
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful();

        $data = $response->json('data');
        $this->assertEquals(3, $data[0]['attributes']['rating']);
        $this->assertEquals(5, $data[2]['attributes']['rating']);
    }

    /**
     * Test can include product relationship.
     */
    public function test_can_include_product_relationship(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->includePaths('product')
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJson([
                'included' => [
                    [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]);
    }

    /**
     * Test can include user relationship.
     */
    public function test_can_include_user_relationship(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();
        $product = Product::factory()->create();

        ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->includePaths('user')
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJson([
                'included' => [
                    [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ]
                ]
            ]);
    }

    /**
     * Test pagination works correctly.
     */
    public function test_pagination_works_correctly(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        ProductReview::factory()->count(25)->approved()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->page(['number' => 1, 'size' => 10])
            ->get('/api/v1/product-reviews');

        $response->assertSuccessful()
            ->assertJsonCount(10, 'data')
            ->assertJson([
                'meta' => [
                    'page' => [
                        'currentPage' => 1,
                        'perPage' => 10,
                        'total' => 25,
                    ]
                ]
            ]);
    }
}

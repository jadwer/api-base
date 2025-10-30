<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Product\Models\Product;
use App\Models\User;

class ProductReviewShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest can view approved product review.
     */
    public function test_guest_can_view_approved_product_review(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->get('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'product-reviews',
                    'id' => (string) $review->id,
                    'attributes' => [
                        'status' => 'approved',
                        'rating' => $review->rating,
                    ]
                ]
            ]);
    }

    /**
     * Test guest cannot view pending product review.
     */
    public function test_guest_cannot_view_pending_product_review(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->get('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot view rejected product review.
     */
    public function test_guest_cannot_view_rejected_product_review(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $review = ProductReview::factory()->rejected()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->get('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(403);
    }

    /**
     * Test admin can view any product review.
     */
    public function test_admin_can_view_any_product_review(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($admin)
            ->get('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'product-reviews',
                    'id' => (string) $review->id,
                ]
            ]);
    }

    /**
     * Test customer can view their own pending review.
     */
    public function test_customer_can_view_their_own_pending_review(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($customer)
            ->get('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'product-reviews',
                    'id' => (string) $review->id,
                    'attributes' => [
                        'status' => 'pending',
                    ]
                ]
            ]);
    }

    /**
     * Test customer cannot view another user's pending review.
     */
    public function test_customer_cannot_view_another_users_pending_review(): void
    {
        $customer = $this->getCustomerUser();
        $otherUser = User::factory()->create();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($customer)
            ->get('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(403);
    }

    /**
     * Test can include product relationship.
     */
    public function test_can_include_product_relationship(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($admin)
            ->includePaths('product')
            ->get('/api/v1/product-reviews/' . $review->id);

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

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($admin)
            ->includePaths('user')
            ->get('/api/v1/product-reviews/' . $review->id);

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
     * Test review has all expected attributes.
     */
    public function test_review_has_all_expected_attributes(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Great product!',
            'comment' => 'I really love this product.',
            'is_verified_purchase' => true,
            'helpful_count' => 10,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($admin)
            ->get('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'product-reviews',
                    'id' => (string) $review->id,
                    'attributes' => [
                        'productId' => $product->id,
                        'userId' => $user->id,
                        'rating' => 5,
                        'title' => 'Great product!',
                        'comment' => 'I really love this product.',
                        'isVerifiedPurchase' => true,
                        'helpfulCount' => 10,
                        'status' => 'approved',
                    ]
                ]
            ]);
    }

    /**
     * Test returns 404 for non-existent review.
     */
    public function test_returns_404_for_non_existent_review(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($admin)
            ->get('/api/v1/product-reviews/99999');

        $response->assertStatus(404);
    }
}

<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Product\Models\Product;
use App\Models\User;

class ProductReviewDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest cannot delete product review.
     */
    public function test_guest_cannot_delete_product_review(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * Test customer can delete their own product review.
     */
    public function test_customer_can_delete_their_own_product_review(): void
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
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * Test customer cannot delete another user's product review.
     */
    public function test_customer_cannot_delete_another_users_product_review(): void
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
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * Test admin can delete any product review.
     */
    public function test_admin_can_delete_any_product_review(): void
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
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * Test tech user can delete any product review.
     */
    public function test_tech_can_delete_any_product_review(): void
    {
        $tech = $this->getTechUser();
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($tech)
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * Test customer can delete their approved review.
     */
    public function test_customer_can_delete_their_approved_review(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($customer)
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * Test customer can delete their rejected review.
     */
    public function test_customer_can_delete_their_rejected_review(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->rejected()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($customer)
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_reviews', [
            'id' => $review->id,
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
            ->delete('/api/v1/product-reviews/99999');

        $response->assertStatus(404);
    }

    /**
     * Test deleting review does not delete associated product.
     */
    public function test_deleting_review_does_not_delete_associated_product(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
        ]);

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($admin)
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    /**
     * Test deleting review does not delete associated user.
     */
    public function test_deleting_review_does_not_delete_associated_user(): void
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
            ->delete('/api/v1/product-reviews/' . $review->id);

        $response->assertNoContent();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Test multiple reviews can be deleted independently.
     */
    public function test_multiple_reviews_can_be_deleted_independently(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $review1 = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
        ]);

        $review2 = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
        ]);

        // Delete first review
        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withToken($customer)
            ->delete('/api/v1/product-reviews/' . $review1->id);

        $response->assertNoContent();

        // Verify first deleted, second still exists
        $this->assertDatabaseMissing('product_reviews', [
            'id' => $review1->id,
        ]);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review2->id,
        ]);
    }
}

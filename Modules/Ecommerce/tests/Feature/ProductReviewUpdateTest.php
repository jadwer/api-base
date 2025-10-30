<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Product\Models\Product;
use App\Models\User;

class ProductReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest cannot update product review.
     */
    public function test_guest_cannot_update_product_review(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'rating' => 4,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(401);
    }

    /**
     * Test customer can update their own product review.
     */
    public function test_customer_can_update_their_own_product_review(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 3,
            'title' => 'Original title',
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'rating' => 5,
                'title' => 'Updated title',
                'comment' => 'Updated comment',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'product-reviews',
                    'id' => (string) $review->id,
                    'attributes' => [
                        'rating' => 5,
                        'title' => 'Updated title',
                        'comment' => 'Updated comment',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'rating' => 5,
            'title' => 'Updated title',
        ]);
    }

    /**
     * Test customer cannot update another user's product review.
     */
    public function test_customer_cannot_update_another_users_product_review(): void
    {
        $customer = $this->getCustomerUser();
        $otherUser = User::factory()->create();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
            'user_id' => $otherUser->id,
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'rating' => 5,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(403);
    }

    /**
     * Test admin can update any product review.
     */
    public function test_admin_can_update_any_product_review(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'status' => 'approved',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'approved',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'status' => 'approved',
        ]);
    }

    /**
     * Test can update only rating.
     */
    public function test_can_update_only_rating(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 3,
            'title' => 'Original title',
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'rating' => 5,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'rating' => 5,
            'title' => 'Original title', // Unchanged
        ]);
    }

    /**
     * Test rating validation on update.
     */
    public function test_rating_validation_on_update(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
        ]);

        // Test rating too low
        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'rating' => 0,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        // Test rating too high
        $data['attributes']['rating'] = 6;

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /**
     * Test title max length validation on update.
     */
    public function test_title_max_length_validation_on_update(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'title' => str_repeat('a', 256),
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test can update status to approved.
     */
    public function test_admin_can_update_status_to_approved(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'status' => 'approved',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'status' => 'approved',
        ]);
    }

    /**
     * Test can update status to rejected.
     */
    public function test_admin_can_update_status_to_rejected(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'status' => 'rejected',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * Test status validation.
     */
    public function test_status_validation(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->pending()->create([
            'product_id' => $product->id,
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'status' => 'invalid-status',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test can update helpful count.
     */
    public function test_admin_can_update_helpful_count(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $review = ProductReview::factory()->approved()->create([
            'product_id' => $product->id,
            'helpful_count' => 5,
        ]);

        $data = [
            'type' => 'product-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'helpfulCount' => 10,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/' . $review->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'helpful_count' => 10,
        ]);
    }

    /**
     * Test returns 404 for non-existent review.
     */
    public function test_returns_404_for_non_existent_review(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'product-reviews',
            'id' => '99999',
            'attributes' => [
                'rating' => 5,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            
            ->patch('/api/v1/product-reviews/99999');

        $response->assertStatus(404);
    }
}

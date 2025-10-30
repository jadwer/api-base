<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Product\Models\Product;
use App\Models\User;

class ProductReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest cannot create product review.
     */
    public function test_guest_cannot_create_product_review(): void
    {
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 5,
                'title' => 'Great product!',
                'comment' => 'I really love this product.',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->post('/api/v1/product-reviews');

        $response->assertStatus(401);
    }

    /**
     * Test customer can create product review.
     */
    public function test_customer_can_create_product_review(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 5,
                'title' => 'Great product!',
                'comment' => 'I really love this product.',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => 'product-reviews',
                    'attributes' => [
                        'userId' => $customer->id,
                        'rating' => 5,
                        'title' => 'Great product!',
                        'comment' => 'I really love this product.',
                        'status' => 'pending', // Default status
                    ]
                ]
            ]);

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'title' => 'Great product!',
            'status' => 'pending',
        ]);
    }

    /**
     * Test admin can create product review.
     */
    public function test_admin_can_create_product_review(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 4,
                'title' => 'Good product',
                'comment' => 'Works well.',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($admin)
            ->post('/api/v1/product-reviews');

        $response->assertCreated();

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'rating' => 4,
        ]);
    }

    /**
     * Test tech can create product review.
     */
    public function test_tech_can_create_product_review(): void
    {
        $tech = $this->getTechUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 3,
                'title' => 'Average product',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($tech)
            ->post('/api/v1/product-reviews');

        $response->assertCreated();
    }

    /**
     * Test product_id is required.
     */
    public function test_product_id_is_required(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 5,
                'title' => 'Great product!',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['productId']);
    }

    /**
     * Test rating is required.
     */
    public function test_rating_is_required(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'title' => 'Great product!',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /**
     * Test title is required.
     */
    public function test_title_is_required(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 5,
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test rating must be between 1 and 5.
     */
    public function test_rating_must_be_between_1_and_5(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        // Test rating too low
        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 0,
                'title' => 'Bad rating',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        // Test rating too high
        $data['attributes']['rating'] = 6;

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /**
     * Test comment is optional.
     */
    public function test_comment_is_optional(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 5,
                'title' => 'Great product!',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertCreated();
    }

    /**
     * Test admin can set status on creation.
     */
    public function test_admin_can_set_status_on_creation(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 5,
                'title' => 'Pre-approved review',
                'status' => 'approved',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($admin)
            ->post('/api/v1/product-reviews');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'approved',
                    ]
                ]
            ]);
    }

    /**
     * Test title has max length validation.
     */
    public function test_title_has_max_length_validation(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 5,
                'title' => str_repeat('a', 256), // Exceeds 255 char limit
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test user_id is automatically set to authenticated user.
     */
    public function test_user_id_is_automatically_set(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-reviews',
            'attributes' => [
                'rating' => 5,
                'title' => 'Great!',
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('product-reviews')
            ->withData($data)
            ->withToken($customer)
            ->post('/api/v1/product-reviews');

        $response->assertCreated();

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $customer->id,
        ]);
    }
}

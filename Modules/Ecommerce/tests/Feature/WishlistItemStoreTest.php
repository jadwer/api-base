<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\Ecommerce\Models\WishlistItem;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Tests\TestCase;

class WishlistItemStoreTest extends TestCase
{
    public function test_admin_can_create_wishlist_item()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 2,
                'priority' => 'high',
                'notes' => 'Need this for the project',
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'wishlist-items',
                'attributes' => [
                    'quantity' => 2,
                    'priority' => 'high',
                    'notes' => 'Need this for the project',
                ],
            ],
        ]);

        $this->assertDatabaseHas('wishlist_items', [
            'wishlist_id' => $wishlist->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'priority' => 'high',
        ]);
    }

    public function test_customer_can_create_wishlist_item()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 1,
                'priority' => 'medium',
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertCreated();
    }

    public function test_tech_user_cannot_create_wishlist_item()
    {
        $tech = User::role('tech')->first();

        $wishlist = Wishlist::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 1,
                'priority' => 'low',
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_wishlist_item()
    {
        $wishlist = Wishlist::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 1,
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertStatus(401);
    }

    public function test_wishlist_is_required()
    {
        $admin = User::role('admin')->first();
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 1,
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertStatus(422);
    }

    public function test_product_is_required()
    {
        $admin = User::role('admin')->first();
        $wishlist = Wishlist::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 1,
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertStatus(422);
    }

    public function test_quantity_must_be_positive()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 0,
                'priority' => 'medium',
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/quantity']
        ]);
    }

    public function test_priority_must_be_valid()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 1,
                'priority' => 'invalid-priority',
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'source' => ['pointer' => '/data/attributes/priority']
        ]);
    }

    public function test_can_create_with_high_priority()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 1,
                'priority' => 'high',
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertCreated();
        $this->assertDatabaseHas('wishlist_items', [
            'priority' => 'high',
        ]);
    }

    public function test_notes_are_optional()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'attributes' => [
                'quantity' => 1,
                'priority' => 'medium',
            ],
            'relationships' => [
                'wishlist' => [
                    'data' => [
                        'type' => 'wishlists',
                        'id' => (string) $wishlist->id,
                    ],
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->post('/api/v1/wishlist-items');

        $response->assertCreated();
    }
}

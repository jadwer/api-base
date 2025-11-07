<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\Ecommerce\Models\WishlistItem;
use Modules\User\Models\User;
use Tests\TestCase;

class WishlistItemIndexTest extends TestCase
{
    /** @test */
    public function admin_can_list_all_wishlist_items()
    {
        $admin = User::role('admin')->first();

        WishlistItem::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'wishlist_id',
                        'product_id',
                        'quantity',
                        'priority',
                        'notes',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_list_wishlist_items()
    {
        $tech = User::role('tech')->first();

        WishlistItem::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items');

        $response->assertSuccessful();
    }

    /** @test */
    public function customer_can_list_wishlist_items()
    {
        $customer = User::role('customer')->first();

        WishlistItem::factory()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items');

        $response->assertSuccessful();
    }

    /** @test */
    public function guest_cannot_list_wishlist_items()
    {
        WishlistItem::factory()->count(3)->create();

        $response = $this->jsonApi()
            ->get('/api/v1/wishlist-items');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_filter_wishlist_items_by_wishlist_id()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();
        WishlistItem::factory()->count(2)->create(['wishlist_id' => $wishlist->id]);
        WishlistItem::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items?filter[wishlistId]=' . $wishlist->id);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_wishlist_items_by_product_id()
    {
        $admin = User::role('admin')->first();

        $item1 = WishlistItem::factory()->create();
        $productId = $item1->product_id;

        WishlistItem::factory()->create(['product_id' => $productId]);
        WishlistItem::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items?filter[productId]=' . $productId);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_wishlist_items_by_priority()
    {
        $admin = User::role('admin')->first();

        WishlistItem::factory()->highPriority()->count(2)->create();
        WishlistItem::factory()->mediumPriority()->count(3)->create();
        WishlistItem::factory()->lowPriority()->count(1)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items?filter[priority]=high');

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_sort_wishlist_items_by_quantity()
    {
        $admin = User::role('admin')->first();

        WishlistItem::factory()->create(['quantity' => 5]);
        WishlistItem::factory()->create(['quantity' => 1]);
        WishlistItem::factory()->create(['quantity' => 3]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items?sort=quantity');

        $response->assertSuccessful();

        $quantities = collect($response->json('data'))->pluck('attributes.quantity')->toArray();
        $this->assertEquals([1, 3, 5], $quantities);
    }

    /** @test */
    public function can_sort_wishlist_items_by_priority()
    {
        $admin = User::role('admin')->first();

        WishlistItem::factory()->create(['priority' => 'medium']);
        WishlistItem::factory()->create(['priority' => 'high']);
        WishlistItem::factory()->create(['priority' => 'low']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items?sort=priority');

        $response->assertSuccessful();

        $priorities = collect($response->json('data'))->pluck('attributes.priority')->toArray();
        $this->assertEquals(['high', 'low', 'medium'], $priorities);
    }

    /** @test */
    public function can_include_wishlist_relationship()
    {
        $admin = User::role('admin')->first();

        WishlistItem::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items?include=wishlist');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'wishlist',
                    ],
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function can_include_product_relationship()
    {
        $admin = User::role('admin')->first();

        WishlistItem::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlist-items?include=product');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'product',
                    ],
                ],
            ],
            'included',
        ]);
    }
}

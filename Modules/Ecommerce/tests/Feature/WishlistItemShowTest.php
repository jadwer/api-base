<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\Ecommerce\Models\WishlistItem;
use Modules\User\Models\User;
use Tests\TestCase;

class WishlistItemShowTest extends TestCase
{
    /** @test */
    public function admin_can_view_any_wishlist_item()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'wishlistId',
                    'productId',
                    'quantity',
                    'priority',
                    'notes',
                    'createdAt',
                    'updatedAt',
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_view_wishlist_item()
    {
        $tech = User::role('tech')->first();

        $item = WishlistItem::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();
    }

    /** @test */
    public function customer_can_view_wishlist_item_from_their_wishlist()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['userId' => $customer->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'wishlist-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'wishlistId' => $wishlist->id,
                    'productId' => $item->product_id,
                    'quantity' => $item->quantity,
                    'priority' => $item->priority,
                ],
            ],
        ]);
    }

    /** @test */
    public function customer_cannot_view_wishlist_item_from_other_users_private_wishlist()
    {
        $customer = User::role('customer')->first();

        $otherUser = User::factory()->create();
        $wishlist = Wishlist::factory()->private()->create(['userId' => $otherUser->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_can_view_wishlist_item_from_public_wishlist()
    {
        $customer = User::role('customer')->first();

        $otherUser = User::factory()->create();
        $wishlist = Wishlist::factory()->public()->create(['userId' => $otherUser->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();
    }

    /** @test */
    public function guest_cannot_view_wishlist_item()
    {
        $item = WishlistItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(401);
    }

    /** @test */
    public function can_include_wishlist_relationship()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id . '?include=wishlist');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'wishlist',
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function can_include_product_relationship()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id . '?include=product');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'product',
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function returns_404_for_non_existent_wishlist_item()
    {
        $admin = User::role('admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function shows_high_priority_item()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->highPriority()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'priority' => 'high',
                ],
            ],
        ]);
    }

    /** @test */
    public function shows_item_with_notes()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->withNotes()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->get('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'attributes' => [
                    'notes',
                ],
            ],
        ]);
    }
}

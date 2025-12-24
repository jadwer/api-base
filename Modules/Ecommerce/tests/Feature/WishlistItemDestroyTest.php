<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\Ecommerce\Models\WishlistItem;
use Modules\User\Models\User;
use Tests\TestCase;

class WishlistItemDestroyTest extends TestCase
{
    public function test_admin_can_delete_any_wishlist_item()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlist_items', [
            'id' => $item->id,
        ]);
    }

    public function test_customer_can_delete_item_from_their_wishlist()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlist_items', [
            'id' => $item->id,
        ]);
    }

    public function test_customer_cannot_delete_item_from_other_users_wishlist()
    {
        $customer = User::role('customer')->first();

        $otherUser = User::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $otherUser->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $item->id,
        ]);
    }

    public function test_tech_user_cannot_delete_wishlist_item()
    {
        $tech = User::role('tech')->first();

        $item = WishlistItem::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $item->id,
        ]);
    }

    public function test_guest_cannot_delete_wishlist_item()
    {
        $item = WishlistItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $item->id,
        ]);
    }

    public function test_returns_404_for_non_existent_wishlist_item()
    {
        $admin = User::role('admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/99999');

        $response->assertStatus(404);
    }

    public function test_can_delete_high_priority_item()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->highPriority()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlist_items', [
            'id' => $item->id,
        ]);
    }

    public function test_can_delete_item_with_notes()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->withNotes()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlist_items', [
            'id' => $item->id,
        ]);
    }

    public function test_deleting_item_does_not_delete_product()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);
        $productId = $item->product_id;

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertNoContent();

        // Verify product still exists
        $this->assertDatabaseHas('products', [
            'id' => $productId,
        ]);
    }

    public function test_deleting_item_does_not_delete_wishlist()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->delete('/api/v1/wishlist-items/' . $item->id);

        $response->assertNoContent();

        // Verify wishlist still exists
        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
        ]);
    }
}

<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\Ecommerce\Models\WishlistItem;
use Modules\User\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WishlistDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /** @test */
    public function admin_can_delete_any_wishlist()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/' . $wishlist->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    /** @test */
    public function customer_can_delete_their_own_wishlist()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/' . $wishlist->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    /** @test */
    public function customer_cannot_delete_other_users_wishlist()
    {
        $customer = User::role('customer')->first();

        $otherUser = User::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    /** @test */
    public function tech_user_cannot_delete_wishlist()
    {
        $tech = User::role('tech')->first();

        $wishlist = Wishlist::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    /** @test */
    public function guest_cannot_delete_wishlist()
    {
        $wishlist = Wishlist::factory()->create();

        $response = $this->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    /** @test */
    public function deleting_wishlist_cascades_to_wishlist_items()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item1 = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);
        $item2 = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/' . $wishlist->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id,
        ]);

        // Verify cascade deletion of items
        $this->assertDatabaseMissing('wishlist_items', [
            'id' => $item1->id,
        ]);

        $this->assertDatabaseMissing('wishlist_items', [
            'id' => $item2->id,
        ]);
    }

    /** @test */
    public function returns_404_for_non_existent_wishlist()
    {
        $admin = User::role('admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function can_delete_public_wishlist_owned_by_user()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->public()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/' . $wishlist->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    /** @test */
    public function can_delete_default_wishlist()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->default()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->delete('/api/v1/wishlists/' . $wishlist->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id,
        ]);
    }
}

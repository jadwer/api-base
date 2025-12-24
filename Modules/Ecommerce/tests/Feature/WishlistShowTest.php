<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\User\Models\User;
use Tests\TestCase;

class WishlistShowTest extends TestCase
{
    public function test_admin_can_view_any_wishlist()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'userId',
                    'name',
                    'isDefault',
                    'isPublic',
                    'createdAt',
                    'updatedAt',
                ],
            ],
        ]);
    }

    public function test_tech_user_can_view_wishlist()
    {
        $tech = User::role('tech')->first();

        $wishlist = Wishlist::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();
    }

    public function test_customer_can_view_their_own_wishlist()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'wishlists',
                'id' => (string) $wishlist->id,
                'attributes' => [
                    'userId' => $customer->id,
                    'name' => $wishlist->name,
                ],
            ],
        ]);
    }

    public function test_customer_cannot_view_other_users_private_wishlist()
    {
        $customer = User::role('customer')->first();

        $otherUser = User::factory()->create();
        $wishlist = Wishlist::factory()->private()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(403);
    }

    public function test_anyone_can_view_public_wishlist()
    {
        $customer = User::role('customer')->first();

        $otherUser = User::factory()->create();
        $wishlist = Wishlist::factory()->public()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'wishlists',
                'id' => (string) $wishlist->id,
                'attributes' => [
                    'isPublic' => true,
                ],
            ],
        ]);
    }

    public function test_guest_cannot_view_wishlist()
    {
        $wishlist = Wishlist::factory()->create();

        $response = $this->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(401);
    }

    public function test_can_include_user_relationship()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id . '?include=user');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'user',
                ],
            ],
            'included',
        ]);
    }

    public function test_can_include_items_relationship()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id . '?include=items');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'items',
                ],
            ],
        ]);
    }

    public function test_can_include_products_relationship()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/' . $wishlist->id . '?include=products');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'products',
                ],
            ],
        ]);
    }

    public function test_returns_404_for_non_existent_wishlist()
    {
        $admin = User::role('admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->get('/api/v1/wishlists/99999');

        $response->assertStatus(404);
    }
}

<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\User\Models\User;
use Tests\TestCase;

class WishlistIndexTest extends TestCase
{
    /** @test */
    public function admin_can_list_all_wishlists()
    {
        $admin = User::role('admin')->first();

        Wishlist::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'user_id',
                        'name',
                        'isDefault',
                        'isPublic',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function tech_user_can_list_wishlists()
    {
        $tech = User::role('tech')->first();

        Wishlist::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists');

        $response->assertSuccessful();
    }

    /** @test */
    public function customer_can_list_their_own_and_public_wishlists()
    {
        $customer = User::role('customer')->first();

        // Create customer's own wishlists
        Wishlist::factory()->count(2)->create(['user_id' => $customer->id]);

        // Create public wishlists from other users
        Wishlist::factory()->public()->count(2)->create();

        // Create private wishlists from other users (should not be visible)
        Wishlist::factory()->private()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists');

        $response->assertSuccessful();
    }

    /** @test */
    public function guest_cannot_list_wishlists()
    {
        Wishlist::factory()->count(3)->create();

        $response = $this->jsonApi()
            ->get('/api/v1/wishlists');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_filter_wishlists_by_user_id()
    {
        $admin = User::role('admin')->first();
        $user = User::factory()->create();

        Wishlist::factory()->count(2)->create(['user_id' => $user->id]);
        Wishlist::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists?filter[userId]=' . $user->id);

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_wishlists_by_is_default()
    {
        $admin = User::role('admin')->first();

        Wishlist::factory()->default()->count(1)->create();
        Wishlist::factory()->count(3)->create(['isDefault' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists?filter[isDefault]=1');

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function can_filter_wishlists_by_is_public()
    {
        $admin = User::role('admin')->first();

        Wishlist::factory()->public()->count(2)->create();
        Wishlist::factory()->private()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists?filter[isPublic]=1');

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_sort_wishlists_by_name()
    {
        $admin = User::role('admin')->first();

        Wishlist::factory()->create(['name' => 'Wishlist C']);
        Wishlist::factory()->create(['name' => 'Wishlist A']);
        Wishlist::factory()->create(['name' => 'Wishlist B']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists?sort=name');

        $response->assertSuccessful();

        $names = collect($response->json('data'))->pluck('attributes.name')->toArray();
        $this->assertEquals(['Wishlist A', 'Wishlist B', 'Wishlist C'], $names);
    }

    /** @test */
    public function can_include_user_relationship()
    {
        $admin = User::role('admin')->first();

        Wishlist::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists?include=user');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'user',
                    ],
                ],
            ],
            'included',
        ]);
    }

    /** @test */
    public function can_include_items_relationship()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/wishlists?include=items');

        $response->assertSuccessful();
    }
}

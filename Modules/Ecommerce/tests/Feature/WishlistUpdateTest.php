<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\User\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WishlistUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    /** @test */
    public function admin_can_update_any_wishlist()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create([
            'name' => 'Original Name',
            'is_default' => false,
            'is_public' => false,
        ]);

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'name' => 'Updated Name',
                'isDefault' => true,
                'isPublic' => true,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'wishlists',
                'id' => (string) $wishlist->id,
                'attributes' => [
                    'name' => 'Updated Name',
                    'isDefault' => true,
                    'isPublic' => true,
                ],
            ],
        ]);

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
            'name' => 'Updated Name',
            'is_default' => true,
            'is_public' => true,
        ]);
    }

    /** @test */
    public function customer_can_update_their_own_wishlist()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create([
            'user_id' => $customer->id,
            'name' => 'Original Wishlist',
            'is_default' => false,
            'is_public' => false,
        ]);

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'name' => 'Updated by Customer',
                'isPublic' => true,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'name' => 'Updated by Customer',
                    'isPublic' => true,
                ],
            ],
        ]);
    }

    /** @test */
    public function customer_cannot_update_other_users_wishlist()
    {
        $customer = User::role('customer')->first();

        $otherUser = User::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $otherUser->id]);

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'name' => 'Trying to Update',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function tech_user_cannot_update_wishlist()
    {
        $tech = User::role('tech')->first();

        $wishlist = Wishlist::factory()->create();

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'name' => 'Tech Update',
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_update_wishlist()
    {
        $wishlist = Wishlist::factory()->create();

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'name' => 'Guest Update',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(401);
    }

    /** @test */
    public function can_update_only_name()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create([
            'user_id' => $customer->id,
            'name' => 'Original',
            'is_default' => false,
            'is_public' => false,
        ]);

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'name' => 'Only Name Changed',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
            'name' => 'Only Name Changed',
            'is_default' => false,
            'is_public' => false,
        ]);
    }

    /** @test */
    public function can_update_only_is_default()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create([
            'user_id' => $customer->id,
            'is_default' => false,
        ]);

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'isDefault' => true,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
            'is_default' => true,
        ]);
    }

    /** @test */
    public function can_update_only_is_public()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create([
            'user_id' => $customer->id,
            'is_public' => false,
        ]);

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'isPublic' => true,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
            'is_public' => true,
        ]);
    }

    /** @test */
    public function name_cannot_exceed_255_characters_on_update()
    {
        $admin = User::role('admin')->first();

        $wishlist = Wishlist::factory()->create();

        $longName = str_repeat('a', 256);

        $data = [
            'type' => 'wishlists',
            'id' => (string) $wishlist->id,
            'attributes' => [
                'name' => $longName,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/' . $wishlist->id);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'El nombre no puede exceder 255 caracteres.',
        ]);
    }

    /** @test */
    public function returns_404_for_non_existent_wishlist()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'wishlists',
            'id' => '99999',
            'attributes' => [
                'name' => 'Update Non-Existent',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlists')
            ->withData($data)
            ->patch('/api/v1/wishlists/99999');

        $response->assertStatus(404);
    }
}

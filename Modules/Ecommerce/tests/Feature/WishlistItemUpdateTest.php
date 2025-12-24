<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Models\Wishlist;
use Modules\Ecommerce\Models\WishlistItem;
use Modules\User\Models\User;
use Tests\TestCase;

class WishlistItemUpdateTest extends TestCase
{
    public function test_admin_can_update_any_wishlist_item()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->create([
            'quantity' => 1,
            'priority' => 'low',
            'notes' => 'Original note',
        ]);

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 5,
                'priority' => 'high',
                'notes' => 'Updated note',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'type' => 'wishlist-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quantity' => 5,
                    'priority' => 'high',
                    'notes' => 'Updated note',
                ],
            ],
        ]);

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $item->id,
            'quantity' => 5,
            'priority' => 'high',
            'notes' => 'Updated note',
        ]);
    }

    public function test_customer_can_update_item_in_their_wishlist()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->create([
            'wishlist_id' => $wishlist->id,
            'quantity' => 1,
            'priority' => 'medium',
        ]);

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 3,
                'priority' => 'high',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'quantity' => 3,
                    'priority' => 'high',
                ],
            ],
        ]);
    }

    public function test_customer_cannot_update_item_in_other_users_wishlist()
    {
        $customer = User::role('customer')->first();

        $otherUser = User::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $otherUser->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 10,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(403);
    }

    public function test_tech_user_cannot_update_wishlist_item()
    {
        $tech = User::role('tech')->first();

        $item = WishlistItem::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 5,
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_wishlist_item()
    {
        $item = WishlistItem::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 5,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(401);
    }

    public function test_can_update_only_quantity()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->create([
            'wishlist_id' => $wishlist->id,
            'quantity' => 1,
            'priority' => 'low',
        ]);

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 7,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $item->id,
            'quantity' => 7,
            'priority' => 'low',
        ]);
    }

    public function test_can_update_only_priority()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->create([
            'wishlist_id' => $wishlist->id,
            'quantity' => 2,
            'priority' => 'low',
        ]);

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'priority' => 'high',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $item->id,
            'quantity' => 2,
            'priority' => 'high',
        ]);
    }

    public function test_can_update_only_notes()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->create([
            'wishlist_id' => $wishlist->id,
            'notes' => 'Old notes',
        ]);

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'notes' => 'New notes',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $item->id,
            'notes' => 'New notes',
        ]);
    }

    public function test_quantity_must_be_positive_on_update()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'quantity' => 0,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'quantity' => 'La cantidad debe ser al menos 1.',
        ]);
    }

    public function test_priority_must_be_valid_on_update()
    {
        $admin = User::role('admin')->first();

        $item = WishlistItem::factory()->create();

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'priority' => 'invalid',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'priority' => 'La prioridad debe ser low, medium o high.',
        ]);
    }

    public function test_can_clear_notes()
    {
        $customer = User::role('customer')->first();

        $wishlist = Wishlist::factory()->create(['user_id' => $customer->id]);
        $item = WishlistItem::factory()->create([
            'wishlist_id' => $wishlist->id,
            'notes' => 'Some notes',
        ]);

        $data = [
            'type' => 'wishlist-items',
            'id' => (string) $item->id,
            'attributes' => [
                'notes' => null,
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/' . $item->id);

        $response->assertSuccessful();

        $this->assertDatabaseHas('wishlist_items', [
            'id' => $item->id,
            'notes' => null,
        ]);
    }

    public function test_returns_404_for_non_existent_wishlist_item()
    {
        $admin = User::role('admin')->first();

        $data = [
            'type' => 'wishlist-items',
            'id' => '99999',
            'attributes' => [
                'quantity' => 5,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('wishlist-items')
            ->withData($data)
            ->patch('/api/v1/wishlist-items/99999');

        $response->assertStatus(404);
    }
}

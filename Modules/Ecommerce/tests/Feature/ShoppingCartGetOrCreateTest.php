<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;
use Illuminate\Support\Str;

class ShoppingCartGetOrCreateTest extends TestCase
{
    public function test_authenticated_user_can_get_or_create_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/get-or-create');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'sessionId',
                    'userId',
                    'status',
                    'expiresAt',
                    'totalAmount',
                    'currency',
                ]
            ]
        ]);

        $this->assertDatabaseHas('shopping_carts', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_returns_existing_active_cart_for_user(): void
    {
        $user = User::factory()->create();

        $existingCart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/get-or-create');

        $response->assertOk();
        $this->assertEquals($existingCart->id, $response->json('data.id'));
    }

    public function test_creates_new_cart_if_existing_is_expired(): void
    {
        $user = User::factory()->create();

        $expiredCart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/get-or-create');

        $response->assertOk();
        $this->assertNotEquals($expiredCart->id, $response->json('data.id'));
    }

    public function test_creates_new_cart_if_existing_is_not_active(): void
    {
        $user = User::factory()->create();

        ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'converted',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/get-or-create');

        $response->assertOk();

        // Should create a new active cart
        $this->assertEquals('active', $response->json('data.attributes.status'));
    }

    public function test_guest_can_get_or_create_cart_with_session_id(): void
    {
        $sessionId = 'test-session-' . uniqid();

        $response = $this->postJson('/api/v1/shopping-carts/get-or-create', [
            'session_id' => $sessionId,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('shopping_carts', [
            'session_id' => $sessionId,
            'user_id' => null,
            'status' => 'active',
        ]);
    }

    public function test_cart_expires_in_7_days(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/get-or-create');

        $response->assertOk();

        $cart = ShoppingCart::find($response->json('data.id'));
        $this->assertTrue($cart->expires_at->isAfter(now()->addDays(6)));
        $this->assertTrue($cart->expires_at->isBefore(now()->addDays(8)));
    }

    public function test_cart_uses_default_currency(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/get-or-create');

        $response->assertOk();
        $this->assertEquals('MXN', $response->json('data.attributes.currency'));
    }

    public function test_new_cart_has_zero_total(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/get-or-create');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.attributes.totalAmount'));
    }
}

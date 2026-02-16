<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Product\Models\Product;

class ShoppingCartCurrentTest extends TestCase
{
    /**
     * Deactivate any existing active carts for the user to avoid test interference.
     */
    private function deactivateUserCarts(int $userId): void
    {
        ShoppingCart::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'abandoned']);
    }

    public function test_authenticated_user_can_get_current_cart(): void
    {
        $user = $this->getCustomerUser();
        $this->deactivateUserCarts($user->id);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/shopping-carts/current');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes',
            ]
        ]);
        $this->assertEquals($cart->id, $response->json('data.id'));
    }

    public function test_returns_null_data_when_no_active_cart_exists(): void
    {
        $user = $this->getCustomerUser();
        $this->deactivateUserCarts($user->id);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/shopping-carts/current');

        $response->assertOk();
        $response->assertJson([
            'data' => null,
            'message' => 'No active cart found',
        ]);
    }

    public function test_returns_null_data_when_cart_is_expired(): void
    {
        $user = $this->getCustomerUser();
        $this->deactivateUserCarts($user->id);

        ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/shopping-carts/current');

        $response->assertOk();
        $response->assertJson(['data' => null]);
    }

    public function test_returns_null_data_when_cart_is_not_active(): void
    {
        $user = $this->getCustomerUser();
        $this->deactivateUserCarts($user->id);

        ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'converted',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/shopping-carts/current');

        $response->assertOk();
        $response->assertJson(['data' => null]);
    }

    public function test_guest_can_get_current_cart_with_session_id(): void
    {
        $sessionId = 'test-session-' . uniqid();

        $cart = ShoppingCart::factory()->create([
            'session_id' => $sessionId,
            'user_id' => null,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/v1/shopping-carts/current?session_id=' . $sessionId);

        $response->assertOk();
        $this->assertEquals($cart->id, $response->json('data.id'));
    }

    public function test_user_cannot_see_other_users_cart(): void
    {
        $user1 = $this->getCustomerUser();
        $this->deactivateUserCarts($user1->id);
        $user2 = User::factory()->create();

        ShoppingCart::factory()->create([
            'user_id' => $user2->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson('/api/v1/shopping-carts/current');

        $response->assertOk();
        $response->assertJson(['data' => null]);
    }

    public function test_response_includes_cart_items(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/shopping-carts/current');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.attributes.itemsCount'));
    }

    public function test_response_includes_calculated_totals(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'total_amount' => 100.00,
            'discount_amount' => 10.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/shopping-carts/current');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'attributes' => [
                    'subtotalAmount',
                    'discountAmount',
                    'finalTotal',
                ]
            ]
        ]);
    }
}

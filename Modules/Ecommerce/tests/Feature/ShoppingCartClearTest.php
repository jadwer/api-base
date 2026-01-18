<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Product\Models\Product;

class ShoppingCartClearTest extends TestCase
{
    public function test_user_can_clear_own_cart(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'total_amount' => 500,
            'coupon_code' => 'DISCOUNT10',
            'discount_amount' => 50,
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->count(3)->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/shopping-carts/{$cart->id}/clear");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Cart cleared successfully',
        ]);

        // Cart items should be deleted
        $this->assertEquals(0, $cart->refresh()->cartItems()->count());

        // Cart totals should be reset
        $this->assertEquals(0, $cart->total_amount);
        $this->assertEquals(0, $cart->discount_amount);
        $this->assertNull($cart->coupon_code);
    }

    public function test_admin_can_clear_any_cart(): void
    {
        $admin = $this->getAdminUser();
        $otherUser = User::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/shopping-carts/{$cart->id}/clear");

        $response->assertOk();
    }

    public function test_user_cannot_clear_other_users_cart(): void
    {
        $user1 = $this->getCustomerUser();
        $user2 = User::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user2->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/v1/shopping-carts/{$cart->id}/clear");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_clear_user_cart(): void
    {
        $user = User::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/shopping-carts/{$cart->id}/clear");

        $response->assertStatus(401);
    }

    public function test_guest_can_clear_own_session_cart(): void
    {
        $sessionId = 'test-session-' . uniqid();

        $cart = ShoppingCart::factory()->create([
            'session_id' => $sessionId,
            'user_id' => null,
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/shopping-carts/{$cart->id}/clear?session_id={$sessionId}"
        );

        $response->assertOk();
        $this->assertEquals(0, $cart->refresh()->cartItems()->count());
    }

    public function test_guest_cannot_clear_other_session_cart(): void
    {
        $cart = ShoppingCart::factory()->create([
            'session_id' => 'original-session',
            'user_id' => null,
            'status' => 'active',
        ]);

        $response = $this->deleteJson(
            "/api/v1/shopping-carts/{$cart->id}/clear?session_id=different-session"
        );

        $response->assertStatus(403);
    }

    public function test_clear_returns_updated_cart_data(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/shopping-carts/{$cart->id}/clear");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'totalAmount',
                    'discountAmount',
                    'itemsCount',
                ]
            ],
            'message',
        ]);

        $this->assertEquals(0, $response->json('data.attributes.totalAmount'));
        $this->assertEquals(0, $response->json('data.attributes.itemsCount'));
    }

    public function test_clear_returns_404_for_nonexistent_cart(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/shopping-carts/99999/clear');

        $response->assertStatus(404);
    }
}

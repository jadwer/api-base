<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\Coupon;

class ShoppingCartRemoveCouponTest extends TestCase
{
    public function test_user_can_remove_coupon_from_own_cart(): void
    {
        $user = $this->getCustomerUser();

        $coupon = Coupon::factory()->create([
            'code' => 'REMOVEME',
            'is_active' => true,
            'used_count' => 5,
        ]);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'coupon_code' => 'REMOVEME',
            'discount_amount' => 50,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/remove-coupon");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Coupon removed successfully',
        ]);

        // Cart should have coupon removed
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $cart->id,
            'coupon_code' => null,
            'discount_amount' => 0,
        ]);

        // Coupon used_count should be decremented
        $this->assertEquals(4, $coupon->refresh()->used_count);
    }

    public function test_remove_coupon_returns_updated_cart(): void
    {
        $user = $this->getCustomerUser();

        Coupon::factory()->create([
            'code' => 'TESTCODE',
            'is_active' => true,
        ]);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'coupon_code' => 'TESTCODE',
            'discount_amount' => 25,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/remove-coupon");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'couponCode',
                    'discountAmount',
                ]
            ],
            'message',
        ]);

        $this->assertNull($response->json('data.attributes.couponCode'));
        $this->assertEquals(0, $response->json('data.attributes.discountAmount'));
    }

    public function test_remove_coupon_when_no_coupon_applied(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'coupon_code' => null,
            'discount_amount' => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/remove-coupon");

        $response->assertOk();
    }

    public function test_user_cannot_remove_coupon_from_other_users_cart(): void
    {
        $user1 = $this->getCustomerUser();
        $user2 = User::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user2->id,
            'status' => 'active',
            'coupon_code' => 'SOMECODE',
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/remove-coupon");

        $response->assertStatus(403);
    }

    public function test_admin_can_remove_coupon_from_any_cart(): void
    {
        $admin = $this->getAdminUser();
        $otherUser = User::factory()->create();

        Coupon::factory()->create([
            'code' => 'ADMINREMOVE',
            'is_active' => true,
        ]);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'active',
            'coupon_code' => 'ADMINREMOVE',
            'discount_amount' => 30,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/remove-coupon");

        $response->assertOk();
    }

    public function test_guest_cannot_remove_coupon_from_user_cart(): void
    {
        $user = User::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'coupon_code' => 'CODE',
        ]);

        $response = $this->postJson("/api/v1/shopping-carts/{$cart->id}/remove-coupon");

        $response->assertStatus(401);
    }

    public function test_guest_can_remove_coupon_from_own_session_cart(): void
    {
        $sessionId = 'test-session-' . uniqid();

        Coupon::factory()->create([
            'code' => 'GUESTCODE',
            'is_active' => true,
        ]);

        $cart = ShoppingCart::factory()->create([
            'session_id' => $sessionId,
            'user_id' => null,
            'status' => 'active',
            'coupon_code' => 'GUESTCODE',
            'discount_amount' => 15,
        ]);

        $response = $this->postJson(
            "/api/v1/shopping-carts/{$cart->id}/remove-coupon?session_id={$sessionId}"
        );

        $response->assertOk();
        $this->assertNull($cart->refresh()->coupon_code);
    }

    public function test_remove_coupon_handles_deleted_coupon(): void
    {
        $user = $this->getCustomerUser();

        // Create cart with coupon code but coupon no longer exists
        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'coupon_code' => 'DELETED_COUPON',
            'discount_amount' => 20,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/remove-coupon");

        // Should still succeed
        $response->assertOk();
        $this->assertNull($cart->refresh()->coupon_code);
    }

    public function test_remove_coupon_returns_404_for_nonexistent_cart(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/shopping-carts/99999/remove-coupon');

        $response->assertStatus(404);
    }
}

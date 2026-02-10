<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\Coupon;
use Modules\Product\Models\Product;

class ShoppingCartApplyCouponTest extends TestCase
{
    public function test_user_can_apply_valid_coupon_to_own_cart(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'total_amount' => 500,
        ]);

        $product = Product::factory()->create(['price' => 500]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 500,
            'subtotal' => 500,
            'total' => 500,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'DISCOUNT20',
            'type' => 'percentage',
            'value' => 20,
            'is_active' => true,
            'max_uses' => 100,
            'used_count' => 0,
            'min_amount' => null, // No minimum order requirement
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'DISCOUNT20',
            ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
            'message' => 'Coupon applied successfully',
        ]);

        $this->assertDatabaseHas('shopping_carts', [
            'id' => $cart->id,
            'coupon_code' => 'DISCOUNT20',
        ]);

        // Coupon used_count should be incremented
        $this->assertEquals(1, $coupon->refresh()->used_count);
    }

    public function test_percentage_coupon_calculates_correct_discount(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create(['price' => 100]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'total' => 100,
        ]);

        Coupon::factory()->create([
            'code' => 'PERCENT10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'min_amount' => null, // No minimum order requirement
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'PERCENT10',
            ]);

        $response->assertOk();
        $this->assertEquals(10.0, $response->json('discount_amount'));
    }

    public function test_fixed_amount_coupon_calculates_correct_discount(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create(['price' => 100]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'total' => 100,
        ]);

        Coupon::factory()->create([
            'code' => 'FIXED25',
            'type' => 'fixed_amount',
            'value' => 25,
            'is_active' => true,
            'min_amount' => null, // No minimum order requirement
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'FIXED25',
            ]);

        $response->assertOk();
        $this->assertEquals(25.0, $response->json('discount_amount'));
    }

    public function test_invalid_coupon_code_returns_error(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'INVALID_CODE',
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'error' => 'Invalid coupon code',
        ]);
    }

    public function test_inactive_coupon_returns_error(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        Coupon::factory()->create([
            'code' => 'INACTIVE',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'INACTIVE',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['valid' => false]);
    }

    public function test_expired_coupon_returns_error(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        Coupon::factory()->create([
            'code' => 'EXPIRED',
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'EXPIRED',
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'error' => 'Coupon has expired',
        ]);
    }

    public function test_coupon_not_yet_active_returns_error(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        Coupon::factory()->create([
            'code' => 'FUTURE',
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'FUTURE',
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'error' => 'Coupon is not yet active',
        ]);
    }

    public function test_coupon_usage_limit_reached_returns_error(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        Coupon::factory()->create([
            'code' => 'LIMITED',
            'is_active' => true,
            'max_uses' => 10,
            'used_count' => 10,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'LIMITED',
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'error' => 'Coupon usage limit reached',
        ]);
    }

    public function test_minimum_amount_not_met_returns_error(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create(['price' => 50]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50,
            'subtotal' => 50,
            'total' => 50,
        ]);

        Coupon::factory()->create([
            'code' => 'MINAMOUNT',
            'is_active' => true,
            'min_amount' => 100,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'MINAMOUNT',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['valid' => false]);
    }

    public function test_coupon_code_is_required(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", []);

        $response->assertStatus(422);
    }

    public function test_user_cannot_apply_coupon_to_other_users_cart(): void
    {
        $user1 = $this->getCustomerUser();
        $user2 = User::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user2->id,
            'status' => 'active',
        ]);

        Coupon::factory()->create([
            'code' => 'VALID',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'VALID',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_apply_coupon_to_any_cart(): void
    {
        $admin = $this->getAdminUser();
        $otherUser = User::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create(['price' => 100]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'total' => 100,
        ]);

        Coupon::factory()->create([
            'code' => 'ADMIN_APPLY',
            'is_active' => true,
            'type' => 'percentage',
            'value' => 10,
            'min_amount' => null, // No minimum order requirement
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'ADMIN_APPLY',
            ]);

        $response->assertOk();
    }

    public function test_max_discount_cap_is_applied(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create(['price' => 1000]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'total' => 1000,
        ]);

        Coupon::factory()->create([
            'code' => 'CAPPED',
            'type' => 'percentage',
            'value' => 50, // 50% = 500
            'max_amount' => 100, // but capped at 100
            'is_active' => true,
            'min_amount' => null, // No minimum order requirement
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/apply-coupon", [
                'coupon_code' => 'CAPPED',
            ]);

        $response->assertOk();
        $this->assertEquals(100.0, $response->json('discount_amount'));
    }
}

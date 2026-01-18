<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Product\Models\Product;

class ShoppingCartMergeTest extends TestCase
{
    public function test_authenticated_user_can_merge_guest_cart(): void
    {
        $user = $this->getCustomerUser();
        $guestSessionId = 'guest-session-' . uniqid();

        // Create guest cart with items
        $guestCart = ShoppingCart::factory()->create([
            'session_id' => $guestSessionId,
            'user_id' => null,
            'status' => 'active',
        ]);

        $product = Product::factory()->create(['price' => 100]);
        CartItem::factory()->create([
            'shopping_cart_id' => $guestCart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/merge', [
                'guest_session_id' => $guestSessionId,
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Carts merged successfully',
        ]);

        // Guest cart should be marked as converted
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $guestCart->id,
            'status' => 'converted',
        ]);
    }

    public function test_merge_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/shopping-carts/merge', [
            'guest_session_id' => 'some-session',
        ]);

        $response->assertStatus(401);
    }

    public function test_merge_requires_guest_session_id(): void
    {
        $user = $this->getCustomerUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/merge', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['guest_session_id']);
    }

    public function test_merge_creates_user_cart_if_not_exists(): void
    {
        $user = $this->getCustomerUser();
        $guestSessionId = 'guest-session-' . uniqid();

        $guestCart = ShoppingCart::factory()->create([
            'session_id' => $guestSessionId,
            'user_id' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/merge', [
                'guest_session_id' => $guestSessionId,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('shopping_carts', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_merge_combines_quantities_for_same_product(): void
    {
        $user = $this->getCustomerUser();
        $guestSessionId = 'guest-session-' . uniqid();

        $product = Product::factory()->create(['price' => 50]);

        // Create user cart with product
        $userCart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $userCart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50,
            'total' => 100,
        ]);

        // Create guest cart with same product
        $guestCart = ShoppingCart::factory()->create([
            'session_id' => $guestSessionId,
            'user_id' => null,
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $guestCart->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 50,
            'total' => 150,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/merge', [
                'guest_session_id' => $guestSessionId,
            ]);

        $response->assertOk();

        // User cart should have combined quantity
        $userCartItem = CartItem::where('shopping_cart_id', $userCart->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertEquals(5, $userCartItem->quantity);
    }

    public function test_merge_moves_unique_items_from_guest_cart(): void
    {
        $user = $this->getCustomerUser();
        $guestSessionId = 'guest-session-' . uniqid();

        $product1 = Product::factory()->create(['price' => 50]);
        $product2 = Product::factory()->create(['price' => 75]);

        // Create user cart with product1
        $userCart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $userCart->id,
            'product_id' => $product1->id,
            'quantity' => 1,
        ]);

        // Create guest cart with product2
        $guestCart = ShoppingCart::factory()->create([
            'session_id' => $guestSessionId,
            'user_id' => null,
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $guestCart->id,
            'product_id' => $product2->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/merge', [
                'guest_session_id' => $guestSessionId,
            ]);

        $response->assertOk();

        // User cart should have both products
        $this->assertEquals(2, $userCart->refresh()->cartItems()->count());
    }

    public function test_merge_succeeds_when_no_guest_cart_exists(): void
    {
        $user = $this->getCustomerUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/merge', [
                'guest_session_id' => 'non-existent-session',
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Carts merged successfully',
        ]);
    }

    public function test_merge_recalculates_totals(): void
    {
        $user = $this->getCustomerUser();
        $guestSessionId = 'guest-session-' . uniqid();

        $product = Product::factory()->create(['price' => 100]);

        $guestCart = ShoppingCart::factory()->create([
            'session_id' => $guestSessionId,
            'user_id' => null,
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $guestCart->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 100,
            'total' => 300,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shopping-carts/merge', [
                'guest_session_id' => $guestSessionId,
            ]);

        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('data.attributes.subtotalAmount'));
    }
}

<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Product\Models\Product;
use Modules\Contacts\Models\Contact;

class ShoppingCartCheckoutTest extends TestCase
{
    public function test_user_can_checkout_own_cart(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'currency' => 'MXN',
        ]);

        $product = Product::factory()->create(['price' => 100]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => [
                    'street' => '123 Main St',
                    'city' => 'Mexico City',
                    'postal_code' => '06600',
                ],
                'shipping_address' => [
                    'street' => '456 Other St',
                    'city' => 'Mexico City',
                    'postal_code' => '06700',
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'orderNumber',
                    'status',
                    'totalAmount',
                ]
            ],
            'message',
        ]);

        $this->assertEquals('sales-orders', $response->json('data.type'));
        $this->assertEquals('Order created successfully', $response->json('message'));

        // Cart should be marked as converted
        $this->assertEquals('converted', $cart->refresh()->status);
    }

    public function test_checkout_creates_sales_order_with_correct_data(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'discount_amount' => 50,
            'tax_amount' => 32,
            'shipping_amount' => 100,
        ]);

        $product = Product::factory()->create(['price' => 200]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 200,
            'total' => 200,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(201);

        // Verify sales order was created
        $this->assertDatabaseHas('sales_orders', [
            'contact_id' => $contact->id,
            'status' => 'pending',
        ]);
    }

    public function test_checkout_creates_order_items_from_cart_items(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 150]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 150,
            'total' => 150,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(201);

        // Verify order items were created
        $this->assertDatabaseHas('sales_order_items', [
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('sales_order_items', [
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);
    }

    public function test_checkout_fails_for_empty_cart(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        // No items in cart

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Cannot checkout empty cart',
        ]);
    }

    public function test_checkout_fails_for_inactive_cart(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'converted',
            'expires_at' => now()->addDays(7),
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Cart is not active',
        ]);
    }

    public function test_checkout_requires_contact_id(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['contact_id']);
    }

    public function test_checkout_requires_billing_address(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['billing_address']);
    }

    public function test_checkout_requires_shipping_address(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shipping_address']);
    }

    public function test_checkout_validates_contact_exists(): void
    {
        $user = $this->getCustomerUser();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => 99999,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['contact_id']);
    }

    public function test_user_cannot_checkout_other_users_cart(): void
    {
        $user1 = $this->getCustomerUser();
        $user2 = User::factory()->create();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user2->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        // User is authenticated but doesn't own this cart - 403 Forbidden
        $response->assertStatus(403);
    }

    public function test_admin_can_checkout_any_cart(): void
    {
        $admin = $this->getAdminUser();
        $otherUser = User::factory()->create();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $product = Product::factory()->create(['price' => 100]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(201);
    }

    public function test_checkout_generates_unique_order_number(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
        ]);

        $product = Product::factory()->create(['price' => 100]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(201);

        $orderNumber = $response->json('data.attributes.orderNumber');
        $this->assertStringStartsWith('OV-', $orderNumber);
    }

    public function test_checkout_returns_404_for_nonexistent_cart(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/shopping-carts/99999/checkout', [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(404);
    }
}

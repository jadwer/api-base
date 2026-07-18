<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\Currency;
use Modules\Product\Models\Product;
use Modules\Contacts\Models\Contact;

class ShoppingCartCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // QA post-commit: el checkout ahora VALIDA stock antes de crear la orden
        // (422 con detalle si no alcanza). Estos tests prueban el flujo del checkout,
        // no el inventario: todo producto creado recibe stock amplio automaticamente
        // para no acoplar cada caso al fixture de inventario.
        $warehouse = \Modules\Inventory\Models\Warehouse::factory()->create();
        Product::created(function ($product) use ($warehouse) {
            \Modules\Inventory\Models\Stock::factory()->create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'warehouse_location_id' => null,
                'quantity' => 10000,
                'reserved_quantity' => 0,
            ]);
        });
    }

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

    public function test_checkout_resolves_contact_from_user_email(): void
    {
        $user = $this->getCustomerUser();
        // Create a contact with the same email as the user
        $contact = Contact::factory()->create(['email' => $user->email]);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'shipping_address' => ['street' => 'Test'],
                'billing_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(201);
    }

    public function test_checkout_creates_contact_on_the_fly_for_user_without_contact(): void
    {
        // User with no matching contact in contacts table
        $user = User::factory()->create([
            'name' => 'Sin Contacto',
            'email' => 'nocontact@example.com',
        ]);
        $user->assignRole('customer');

        $this->assertDatabaseMissing('contacts', ['email' => 'nocontact@example.com']);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'shipping_address' => ['street' => 'Test'],
                'billing_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(201);

        // Contact was created on-the-fly from the user data
        $this->assertDatabaseHas('contacts', [
            'email' => 'nocontact@example.com',
            'name' => 'Sin Contacto',
            'contact_type' => 'person',
            'is_customer' => true,
            'status' => 'active',
        ]);

        // Order is linked to the new contact (my-orders filters by contact_email)
        $contact = Contact::where('email', 'nocontact@example.com')->first();
        $this->assertDatabaseHas('sales_orders', [
            'contact_id' => $contact->id,
            'status' => 'pending',
        ]);
    }

    public function test_guest_cannot_checkout(): void
    {
        // Checkout route requires auth:sanctum; guests never reach the controller
        $cart = ShoppingCart::factory()->create([
            'user_id' => null,
            'session_id' => 'guest-session-401',
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
            'session_id' => 'guest-session-401',
            'shipping_address' => ['street' => 'Test'],
            'billing_address' => ['street' => 'Test'],
        ]);

        $response->assertStatus(401);
    }

    public function test_checkout_reuses_existing_contact_for_user_without_contact_id(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);
        $user->assignRole('customer');

        $existingContact = Contact::factory()->create(['email' => 'existing@example.com']);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'shipping_address' => ['street' => 'Test'],
                'billing_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(201);

        // No duplicate contact created
        $this->assertEquals(1, Contact::where('email', 'existing@example.com')->count());
        $this->assertDatabaseHas('sales_orders', [
            'contact_id' => $existingContact->id,
        ]);
    }

    public function test_checkout_uses_shipping_as_billing_when_not_provided(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'shipping_address' => ['street' => 'Test Street', 'city' => 'CDMX'],
            ]);

        $response->assertStatus(201);
    }

    public function test_checkout_validates_contact_exists_when_provided(): void
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

    public function test_checkout_propagates_currency_to_sales_order(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        // Ensure MXN and USD currencies exist
        $mxn = Currency::firstOrCreate(['code' => 'MXN'], [
            'name' => 'Mexican Peso', 'symbol' => '$', 'exchange_rate' => 1.0, 'is_active' => true,
        ]);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'currency' => 'MXN',
            'currency_id' => $mxn->id,
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

        // Verify sales order has currency field set
        $this->assertDatabaseHas('sales_orders', [
            'contact_id' => $contact->id,
            'currency' => 'MXN',
        ]);
    }

    public function test_checkout_propagates_currency_traceability_to_order_items(): void
    {
        $user = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'currency' => 'MXN',
        ]);

        $product = Product::factory()->create(['price' => 1750]);

        // Bypass observer to set exact currency traceability fields for testing propagation
        CartItem::withoutEvents(function () use ($cart, $product) {
            CartItem::factory()->create([
                'shopping_cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 1750,
                'total' => 1750,
                'original_currency_code' => 'USD',
                'original_unit_price' => 100,
                'exchange_rate_used' => 17.5,
            ]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'contact_id' => $contact->id,
                'billing_address' => ['street' => 'Test'],
                'shipping_address' => ['street' => 'Test'],
            ]);

        $response->assertStatus(201);

        // Verify order items have currency traceability fields
        $this->assertDatabaseHas('sales_order_items', [
            'product_id' => $product->id,
            'original_currency_code' => 'USD',
            'original_unit_price' => 100,
            'exchange_rate_used' => 17.5,
        ]);
    }
}

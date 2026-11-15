<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Product\Models\Product;
use Tests\TestCase;

class QuoteFromCartTest extends TestCase
{
    public function test_admin_can_create_quote_from_cart(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $cart = ShoppingCart::factory()->create(['user_id' => $admin->id]);
        $product = Product::factory()->create(['price' => 100]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart->id,
                'contact_id' => $contact->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'quoteNumber',
                    'status',
                    'totalAmount',
                ],
            ],
            'message',
        ]);
        $this->assertEquals('draft', $response->json('data.attributes.status'));
    }

    public function test_tech_can_create_quote_from_cart(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $cart = ShoppingCart::factory()->create(['user_id' => $tech->id]);
        $product = Product::factory()->create(['price' => 50]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart->id,
                'contact_id' => $contact->id,
            ]);

        $response->assertStatus(201);
    }

    public function test_guest_cannot_create_quote_from_cart(): void
    {
        $contact = Contact::factory()->customer()->create();
        $cart = ShoppingCart::factory()->create();
        $product = Product::factory()->create();

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/v1/quotes/from-cart', [
            'shopping_cart_id' => $cart->id,
            'contact_id' => $contact->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_create_quote_from_empty_cart(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $cart = ShoppingCart::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart->id,
                'contact_id' => $contact->id,
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'No se puede crear una cotización con el carrito vacío.',
        ]);
    }

    public function test_requires_shopping_cart_id(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'contact_id' => $contact->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shopping_cart_id']);
    }

    /** Carrito con una partida para el usuario indicado. */
    private function cartFor(?int $userId): ShoppingCart
    {
        $cart = ShoppingCart::factory()->create(['user_id' => $userId]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => Product::factory()->create(['price' => 100])->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        return $cart;
    }

    public function test_a_customer_quoting_their_own_cart_becomes_the_contact(): void
    {
        $customer = $this->getCustomerUser();
        $cart = $this->cartFor($customer->id);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', ['shopping_cart_id' => $cart->id])
            ->assertStatus(201);

        $this->assertDatabaseHas('quotes', ['shopping_cart_id' => $cart->id, 'contact_id' => Contact::where('email', $customer->email)->value('id')]);
    }

    public function test_staff_converting_a_customer_cart_links_the_cart_owner_not_the_staff(): void
    {
        // Junta 2026-11-12 (J4): antes se tomaba el correo de quien la creaba.
        $customer = $this->getCustomerUser();
        $cart = $this->cartFor($customer->id);
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', ['shopping_cart_id' => $cart->id])
            ->assertStatus(201);

        $this->assertDatabaseHas('quotes', ['shopping_cart_id' => $cart->id, 'contact_id' => Contact::where('email', $customer->email)->value('id')]);
        $this->assertDatabaseMissing('contacts', ['email' => $admin->email, 'is_customer' => true]);
    }

    public function test_a_cart_without_a_registered_customer_asks_for_the_contact_in_spanish(): void
    {
        $admin = $this->getAdminUser();
        $cart = $this->cartFor($admin->id);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', ['shopping_cart_id' => $cart->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Elige el cliente de la cotización: el carrito no es de un cliente registrado.');
        $this->assertDatabaseMissing('quotes', ['shopping_cart_id' => $cart->id]);
    }

    public function test_a_customer_cannot_quote_someone_elses_cart(): void
    {
        $other = \Modules\User\Models\User::factory()->create();
        $other->assignRole('customer');
        $cart = $this->cartFor($other->id);

        $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', ['shopping_cart_id' => $cart->id])
            ->assertStatus(403);
        $this->assertDatabaseMissing('quotes', ['shopping_cart_id' => $cart->id]);
    }

    public function test_carts_list_only_with_items_and_says_who_owns_them(): void
    {
        $customer = $this->getCustomerUser();
        $full = $this->cartFor($customer->id);
        $empty = ShoppingCart::factory()->create(['user_id' => $customer->id]);

        $data = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()->expects('shopping-carts')
            ->get('/api/v1/shopping-carts?filter[withItems]=1&page[size]=100')
            ->assertOk()->json('data');

        $ids = array_column($data, 'id');
        $this->assertContains((string) $full->id, $ids);
        $this->assertNotContains((string) $empty->id, $ids);
        $row = collect($data)->firstWhere('id', (string) $full->id);
        $this->assertTrue($row['attributes']['ownerIsCustomer']);
        $this->assertSame($customer->email, $row['attributes']['ownerEmail']);
    }

    public function test_returns_404_for_nonexistent_cart(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => 99999,
                'contact_id' => $contact->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_creates_quote_items_from_cart_items(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $cart = ShoppingCart::factory()->create(['user_id' => $admin->id]);
        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 200]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 100,
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 200,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart->id,
                'contact_id' => $contact->id,
            ]);

        $response->assertStatus(201);
        $quoteId = $response->json('data.id');

        $this->assertDatabaseCount('quote_items', 2);
        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $quoteId,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);
    }

    public function test_can_include_notes_and_valid_until(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $cart = ShoppingCart::factory()->create(['user_id' => $admin->id]);
        $product = Product::factory()->create(['price' => 100]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $validUntil = now()->addDays(15)->toDateString();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart->id,
                'contact_id' => $contact->id,
                'notes' => 'Test quote notes',
                'valid_until' => $validUntil,
            ]);

        $response->assertStatus(201);
        $this->assertEquals('Test quote notes', $response->json('data.attributes.notes'));
    }
}

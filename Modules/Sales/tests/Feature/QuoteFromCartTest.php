<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Product\Models\Product;

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
            'error' => 'Cannot create quote from empty cart',
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

    public function test_missing_contact_id_resolves_or_creates_contact_from_user(): void
    {
        // Customers quoting their own cart do not send contact_id: the
        // controller resolves it by user email or creates the contact on the
        // fly (same behavior as checkout).
        $admin = $this->getAdminUser();
        $cart = ShoppingCart::factory()->create(['user_id' => $admin->id]);
        $product = Product::factory()->create(['price' => 100]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', ['email' => $admin->email]);
        $contactId = Contact::where('email', $admin->email)->value('id');
        $this->assertDatabaseHas('quotes', [
            'shopping_cart_id' => $cart->id,
            'contact_id' => $contactId,
        ]);
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

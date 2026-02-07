<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Product\Models\Product;
use Modules\Product\Models\Brand;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

/**
 * Tests for the customer quote request endpoint
 * POST /api/v1/quotes/request
 *
 * This endpoint allows authenticated customers to request quotes
 * without needing to access the dashboard or select a contact.
 */
class QuoteRequestTest extends TestCase
{
    public function test_authenticated_user_can_request_quote(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'quote_number',
                'total_amount',
                'items_count',
                'valid_until',
            ],
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(1, $response->json('data.items_count'));
    }

    public function test_unauthenticated_user_cannot_request_quote(): void
    {
        $product = Product::factory()->create([
            'price' => 100,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/quotes/request', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_creates_contact_for_user_if_not_exists(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer_test_unique@example.com',
        ]);
        $product = Product::factory()->create([
            'price' => 50,
            'is_active' => true,
        ]);

        // Ensure no contact exists for this user
        Contact::where('email', $user->email)->delete();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);

        // Verify contact was created
        $this->assertDatabaseHas('contacts', [
            'email' => $user->email,
            'name' => $user->name,
            'is_customer' => true,
        ]);
    }

    public function test_uses_existing_contact_if_exists(): void
    {
        $user = User::factory()->create([
            'email' => 'existing_contact_test@example.com',
        ]);
        $existingContact = Contact::factory()->create([
            'email' => $user->email,
            'name' => 'Existing Contact',
            'is_customer' => true,
        ]);
        $product = Product::factory()->create([
            'price' => 75,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ]);

        $response->assertStatus(201);

        // Verify quote uses existing contact
        $quote = Quote::where('quote_number', $response->json('data.quote_number'))->first();
        $this->assertEquals($existingContact->id, $quote->contact_id);
    }

    public function test_validates_required_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    public function test_validates_items_must_be_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => 'not an array',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    public function test_validates_product_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => 999999, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_validates_quantity_is_positive(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 0],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_rejects_inactive_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'One or more products are not available',
        ]);
    }

    public function test_can_request_quote_with_multiple_items(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 100, 'is_active' => true]);
        $product2 = Product::factory()->create(['price' => 200, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product1->id, 'quantity' => 2],
                    ['product_id' => $product2->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertEquals(2, $response->json('data.items_count'));
    }

    public function test_quote_is_automatically_marked_as_sent(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);

        $quote = Quote::where('quote_number', $response->json('data.quote_number'))->first();
        $this->assertEquals('sent', $quote->status);
        $this->assertNotNull($quote->sent_at);
    }

    public function test_includes_eta_from_brand_lead_time(): void
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create(['default_lead_time' => 15]);
        $product = Product::factory()->create([
            'price' => 100,
            'is_active' => true,
            'brand_id' => $brand->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);

        $quote = Quote::where('quote_number', $response->json('data.quote_number'))->first();
        $quoteItem = $quote->items->first();
        $this->assertStringContainsString('15', $quoteItem->notes);
    }

    public function test_can_include_notes_in_request(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'notes' => 'Please deliver before next week',
            ]);

        $response->assertStatus(201);

        $quote = Quote::where('quote_number', $response->json('data.quote_number'))->first();
        $this->assertEquals('Please deliver before next week', $quote->notes);
    }

    public function test_customer_role_can_request_quote(): void
    {
        $customer = $this->getCustomerUser();
        $product = Product::factory()->create(['price' => 100, 'is_active' => true]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
    }
}

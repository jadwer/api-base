<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteConvertTest extends TestCase
{
    public function test_admin_can_convert_accepted_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
            'subtotal_amount' => 200,
            'tax_amount' => 32,
            'total_amount' => 232,
        ]);
        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'quoted_price' => 100,
            'total' => 200,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Quote converted to sales order successfully',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'quote',
                'salesOrder' => [
                    'type',
                    'id',
                    'attributes' => [
                        'orderNumber',
                        'status',
                        'totalAmount',
                    ],
                ],
            ],
        ]);

        $this->assertEquals('converted', $response->json('data.quote.attributes.status'));
        $this->assertNotNull($response->json('data.quote.attributes.convertedAt'));
    }

    public function test_tech_can_convert_quote(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
        ]);
        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);
    }

    public function test_guest_cannot_convert_quote(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
        ]);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(401);
    }

    public function test_cannot_convert_draft_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Quote cannot be converted. It must be in accepted status and not already converted.',
        ]);
    }

    public function test_cannot_convert_sent_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(400);
    }

    public function test_cannot_convert_already_converted_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(400);
    }

    public function test_creates_sales_order_with_quote_items(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
        ]);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'quoted_price' => 100,
        ]);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'quoted_price' => 50,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);

        $orderId = $response->json('data.salesOrder.id');
        $this->assertDatabaseHas('sales_order_items', [
            'sales_order_id' => $orderId,
            'product_id' => $product1->id,
        ]);
        $this->assertDatabaseHas('sales_order_items', [
            'sales_order_id' => $orderId,
            'product_id' => $product2->id,
        ]);
    }

    public function test_can_override_addresses_on_convert(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
        ]);
        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'shipping_address' => ['street' => '123 New St'],
                'billing_address' => ['street' => '456 Bill St'],
            ]);

        $response->assertStatus(201);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/99999/convert');

        $response->assertStatus(404);
    }
}

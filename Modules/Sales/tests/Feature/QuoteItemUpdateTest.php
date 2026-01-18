<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteItemUpdateTest extends TestCase
{
    public function test_admin_can_update_quote_item(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quantity' => 5,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $this->assertEquals(5, $item->refresh()->quantity);
    }

    public function test_guest_cannot_update_quote_item(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quantity' => 10,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertStatus(401);
    }

    public function test_can_update_quoted_price(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'unit_price' => 100,
            'quoted_price' => 100,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'quotedPrice' => 85,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $this->assertEquals(85, $item->refresh()->quoted_price);
    }

    public function test_can_update_discount_percentage(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'discount_percentage' => 0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'discountPercentage' => 15,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $this->assertEquals(15, $item->refresh()->discount_percentage);
    }

    public function test_can_update_tax_rate(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'tax_rate' => 16,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => (string) $item->id,
                'attributes' => [
                    'taxRate' => 8,
                ],
            ])
            ->patch("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $this->assertEquals(8, $item->refresh()->tax_rate);
    }

    public function test_returns_404_for_nonexistent_quote_item(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->withData([
                'type' => 'quote-items',
                'id' => '99999',
                'attributes' => [
                    'quantity' => 1,
                ],
            ])
            ->patch('/api/v1/quote-items/99999');

        $response->assertStatus(404);
    }
}

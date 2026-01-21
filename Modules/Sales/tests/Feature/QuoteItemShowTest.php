<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteItemShowTest extends TestCase
{
    public function test_admin_can_view_quote_item(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'quoted_price' => 95,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->get("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'quantity',
                    'unitPrice',
                    'quotedPrice',
                    'discountPercentage',
                    'taxRate',
                    'subtotalBeforeDiscount',
                    'subtotalAfterDiscount',
                    'total',
                ]
            ]
        ]);
    }

    public function test_guest_cannot_view_quote_item(): void
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
            ->get("/api/v1/quote-items/{$item->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_quote_item(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->get('/api/v1/quote-items/99999');

        $response->assertStatus(404);
    }

    public function test_can_include_product_relationship(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->includePaths('product')
            ->get("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
    }

    public function test_can_include_quote_relationship(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->includePaths('quote')
            ->get("/api/v1/quote-items/{$item->id}");

        $response->assertOk();
    }
}

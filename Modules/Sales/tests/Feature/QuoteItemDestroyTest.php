<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteItemDestroyTest extends TestCase
{
    public function test_admin_can_delete_quote_item(): void
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
            ->delete("/api/v1/quote-items/{$item->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('quote_items', ['id' => $item->id]);
    }

    public function test_tech_can_delete_quote_item(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->delete("/api/v1/quote-items/{$item->id}");

        $response->assertNoContent();
    }

    public function test_guest_cannot_delete_quote_item(): void
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
            ->delete("/api/v1/quote-items/{$item->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_quote_item(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->delete('/api/v1/quote-items/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_same_item_twice_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);
        $product = Product::factory()->create();

        $item = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        // First delete
        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->delete("/api/v1/quote-items/{$item->id}");

        // Second delete
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quote-items')
            ->delete("/api/v1/quote-items/{$item->id}");

        $response->assertStatus(404);
    }
}

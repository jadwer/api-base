<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteDuplicateTest extends TestCase
{
    public function test_admin_can_duplicate_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'notes' => 'Original notes',
        ]);
        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'quoted_price' => 100,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/duplicate");

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Quote duplicated successfully',
        ]);

        // Duplicated quote should be draft
        $this->assertEquals('draft', $response->json('data.attributes.status'));

        // Should have different quote number
        $this->assertNotEquals($quote->quote_number, $response->json('data.attributes.quoteNumber'));

        // Should preserve notes
        $this->assertEquals('Original notes', $response->json('data.attributes.notes'));
    }

    public function test_tech_can_duplicate_quote(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/duplicate");

        $response->assertStatus(201);
    }

    public function test_guest_cannot_duplicate_quote(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/duplicate");

        $response->assertStatus(401);
    }

    public function test_duplicate_copies_quote_items(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/duplicate");

        $response->assertStatus(201);

        $newQuoteId = $response->json('data.id');

        // Should have copied the items
        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $newQuoteId,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $newQuoteId,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);
    }

    public function test_duplicate_resets_status_timestamps(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'sent_at' => now()->subDays(5),
            'accepted_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/duplicate");

        $response->assertStatus(201);

        $this->assertEquals('draft', $response->json('data.attributes.status'));
        $this->assertNull($response->json('data.attributes.sentAt'));
        $this->assertNull($response->json('data.attributes.acceptedAt'));
    }

    public function test_can_duplicate_any_status_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        // Test duplicating a converted quote
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'converted',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/duplicate");

        $response->assertStatus(201);
        $this->assertEquals('draft', $response->json('data.attributes.status'));
    }

    public function test_duplicate_sets_new_valid_until_date(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'valid_until' => now()->subDays(10), // Expired
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/duplicate");

        $response->assertStatus(201);

        // New quote should have future valid_until
        $newValidUntil = $response->json('data.attributes.validUntil');
        $this->assertGreaterThan(now()->toISOString(), $newValidUntil);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/99999/duplicate');

        $response->assertStatus(404);
    }
}

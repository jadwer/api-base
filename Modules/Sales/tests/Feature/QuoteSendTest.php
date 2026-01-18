<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteSendTest extends TestCase
{
    public function test_admin_can_send_draft_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);
        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Quote sent successfully',
        ]);
        $this->assertEquals('sent', $response->json('data.attributes.status'));
        $this->assertNotNull($response->json('data.attributes.sentAt'));
    }

    public function test_tech_can_send_quote(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);
        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertOk();
    }

    public function test_guest_cannot_send_quote(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertStatus(401);
    }

    public function test_cannot_send_already_sent_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Quote cannot be sent. It must be in draft status with at least one item.',
        ]);
    }

    public function test_cannot_send_quote_without_items(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertStatus(400);
    }

    public function test_cannot_send_converted_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'converted',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertStatus(400);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/99999/send');

        $response->assertStatus(404);
    }
}

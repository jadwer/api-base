<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteDestroyTest extends TestCase
{
    public function test_admin_can_delete_quote(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->delete("/api/v1/quotes/{$quote->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
    }

    public function test_tech_can_delete_quote(): void
    {
        $tech = $this->getTechUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->delete("/api/v1/quotes/{$quote->id}");

        $response->assertNoContent();
    }

    public function test_guest_cannot_delete_quote(): void
    {
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $response = $this->jsonApi()
            ->expects('quotes')
            ->delete("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->delete('/api/v1/quotes/99999');

        $response->assertStatus(404);
    }

    public function test_can_delete_quote_with_items(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $product = Product::factory()->create();
        QuoteItem::factory()->count(3)->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->delete("/api/v1/quotes/{$quote->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
    }

    public function test_can_delete_draft_quote(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->delete("/api/v1/quotes/{$quote->id}");

        $response->assertNoContent();
    }

    public function test_can_delete_sent_quote(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->delete("/api/v1/quotes/{$quote->id}");

        $response->assertNoContent();
    }

    public function test_deleting_same_quote_twice_returns_404(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
        ]);

        // First delete
        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->delete("/api/v1/quotes/{$quote->id}");

        // Second delete
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->delete("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(404);
    }
}

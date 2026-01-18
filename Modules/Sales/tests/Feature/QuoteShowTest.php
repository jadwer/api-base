<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Product\Models\Product;

class QuoteShowTest extends TestCase
{
    public function test_admin_can_view_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'quoteNumber',
                    'status',
                    'quoteDate',
                    'validUntil',
                    'subtotalAmount',
                    'taxAmount',
                    'totalAmount',
                    'currency',
                    'estimatedEta',
                    'notes',
                ]
            ]
        ]);
    }

    public function test_tech_can_view_quote(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
    }

    public function test_customer_can_view_quote(): void
    {
        $customer = $this->getCustomerUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
    }

    public function test_guest_cannot_view_quote(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);

        $response = $this->jsonApi()
            ->expects('quotes')
            ->get("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get('/api/v1/quotes/99999');

        $response->assertStatus(404);
    }

    public function test_can_include_items_relationship(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);

        $product = Product::factory()->create();
        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->includePaths('quoteItems')
            ->get("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'included' => [
                '*' => [
                    'type',
                    'id',
                    'attributes',
                ]
            ]
        ]);
    }

    public function test_can_include_contact_relationship(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create(['contact_id' => $contact->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->includePaths('contact')
            ->get("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
    }

    public function test_shows_correct_status(): void
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
            ->get("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
        $this->assertEquals('sent', $response->json('data.attributes.status'));
    }

    public function test_shows_calculated_fields(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'subtotal_amount' => 1000,
            'tax_amount' => 160,
            'total_amount' => 1160,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->get("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
        $this->assertEquals(1000, $response->json('data.attributes.subtotalAmount'));
        $this->assertEquals(160, $response->json('data.attributes.taxAmount'));
        $this->assertEquals(1160, $response->json('data.attributes.totalAmount'));
    }
}

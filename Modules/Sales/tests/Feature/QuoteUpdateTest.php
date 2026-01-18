<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteUpdateTest extends TestCase
{
    public function test_admin_can_update_quote(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
            'notes' => 'Original notes',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'id' => (string) $quote->id,
                'attributes' => [
                    'notes' => 'Updated notes',
                ],
            ])
            ->patch("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
        $this->assertEquals('Updated notes', $quote->refresh()->notes);
    }

    public function test_tech_can_update_quote(): void
    {
        $tech = $this->getTechUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'id' => (string) $quote->id,
                'attributes' => [
                    'estimatedEta' => '1-2 weeks',
                ],
            ])
            ->patch("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
    }

    public function test_guest_cannot_update_quote(): void
    {
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'id' => (string) $quote->id,
                'attributes' => [
                    'notes' => 'Hacked notes',
                ],
            ])
            ->patch("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(401);
    }

    public function test_can_update_estimated_eta(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'estimated_eta' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'id' => (string) $quote->id,
                'attributes' => [
                    'estimatedEta' => '3-4 weeks for delivery',
                ],
            ])
            ->patch("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
        $this->assertEquals('3-4 weeks for delivery', $quote->refresh()->estimated_eta);
    }

    public function test_can_update_totals(): void
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
            ->withData([
                'type' => 'quotes',
                'id' => (string) $quote->id,
                'attributes' => [
                    'subtotalAmount' => 2000,
                    'taxAmount' => 320,
                    'totalAmount' => 2320,
                ],
            ])
            ->patch("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
        $quote->refresh();
        $this->assertEquals(2000, $quote->subtotal_amount);
        $this->assertEquals(320, $quote->tax_amount);
        $this->assertEquals(2320, $quote->total_amount);
    }

    public function test_can_update_valid_until(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'valid_until' => now()->addDays(15),
        ]);

        $newDate = now()->addDays(45)->toDateString();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'id' => (string) $quote->id,
                'attributes' => [
                    'validUntil' => $newDate,
                ],
            ])
            ->patch("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
    }

    public function test_quote_number_uniqueness_ignores_current_record(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'quote_number' => 'QT-UNIQUE-001',
        ]);

        // Updating with the same quote number should work
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'id' => (string) $quote->id,
                'attributes' => [
                    'quoteNumber' => 'QT-UNIQUE-001',
                    'notes' => 'Updated notes',
                ],
            ])
            ->patch("/api/v1/quotes/{$quote->id}");

        $response->assertOk();
    }

    public function test_cannot_update_to_existing_quote_number(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'quote_number' => 'QT-EXISTING',
        ]);

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'quote_number' => 'QT-TO-UPDATE',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'id' => (string) $quote->id,
                'attributes' => [
                    'quoteNumber' => 'QT-EXISTING',
                ],
            ])
            ->patch("/api/v1/quotes/{$quote->id}");

        $response->assertStatus(422);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('quotes')
            ->withData([
                'type' => 'quotes',
                'id' => '99999',
                'attributes' => [
                    'notes' => 'Test',
                ],
            ])
            ->patch('/api/v1/quotes/99999');

        $response->assertStatus(404);
    }
}

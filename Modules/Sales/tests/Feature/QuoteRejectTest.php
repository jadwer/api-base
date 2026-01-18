<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteRejectTest extends TestCase
{
    public function test_admin_can_reject_sent_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/reject");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Quote rejected',
        ]);
        $this->assertEquals('rejected', $response->json('data.attributes.status'));
        $this->assertNotNull($response->json('data.attributes.rejectedAt'));
    }

    public function test_admin_can_reject_draft_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/reject");

        $response->assertOk();
        $this->assertEquals('rejected', $response->json('data.attributes.status'));
    }

    public function test_tech_can_reject_quote(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/reject");

        $response->assertOk();
    }

    public function test_guest_cannot_reject_quote(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/reject");

        $response->assertStatus(401);
    }

    public function test_can_include_rejection_reason(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'internal_notes' => 'Initial notes',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/reject", [
                'reason' => 'Price too high',
            ]);

        $response->assertOk();
        $quote->refresh();
        $this->assertStringContainsString('Price too high', $quote->internal_notes);
    }

    public function test_cannot_reject_accepted_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/reject");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'This quote cannot be rejected',
        ]);
    }

    public function test_cannot_reject_converted_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'converted',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/reject");

        $response->assertStatus(400);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/99999/reject');

        $response->assertStatus(404);
    }
}

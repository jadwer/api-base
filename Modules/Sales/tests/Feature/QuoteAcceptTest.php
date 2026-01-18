<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteAcceptTest extends TestCase
{
    public function test_admin_can_accept_sent_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Quote accepted successfully',
        ]);
        $this->assertEquals('accepted', $response->json('data.attributes.status'));
        $this->assertNotNull($response->json('data.attributes.acceptedAt'));
    }

    public function test_tech_can_accept_quote(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertOk();
    }

    public function test_guest_cannot_accept_quote(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(401);
    }

    public function test_cannot_accept_draft_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Only sent quotes can be accepted',
        ]);
    }

    public function test_cannot_accept_already_accepted_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(400);
    }

    public function test_cannot_accept_rejected_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(400);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/99999/accept');

        $response->assertStatus(404);
    }
}

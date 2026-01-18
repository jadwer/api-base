<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteCancelTest extends TestCase
{
    public function test_admin_can_cancel_draft_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/cancel");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Quote cancelled successfully',
        ]);
        $this->assertEquals('cancelled', $response->json('data.attributes.status'));
    }

    public function test_admin_can_cancel_sent_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/cancel");

        $response->assertOk();
        $this->assertEquals('cancelled', $response->json('data.attributes.status'));
    }

    public function test_tech_can_cancel_quote(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/cancel");

        $response->assertOk();
    }

    public function test_guest_cannot_cancel_quote(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/cancel");

        $response->assertStatus(401);
    }

    public function test_cannot_cancel_converted_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/cancel");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'This quote cannot be cancelled',
        ]);
    }

    public function test_cannot_cancel_already_cancelled_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/cancel");

        $response->assertStatus(400);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/99999/cancel');

        $response->assertStatus(404);
    }
}

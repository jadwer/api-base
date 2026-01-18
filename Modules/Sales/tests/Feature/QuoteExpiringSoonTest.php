<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteExpiringSoonTest extends TestCase
{
    public function test_admin_can_get_expiring_quotes(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        // Quote expiring in 3 days
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(3),
        ]);

        // Quote expiring in 5 days
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(5),
        ]);

        // Quote expiring in 10 days (should not appear in default 7 days)
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(10),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/expiring-soon');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'count',
                'days',
            ],
        ]);

        // Should return 2 quotes (within 7 days default)
        $this->assertEquals(2, $response->json('meta.count'));
    }

    public function test_can_specify_days_parameter(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(3),
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(15),
        ]);

        // Request with 5 days
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/expiring-soon?days=5');

        $response->assertOk();
        $this->assertEquals(5, $response->json('meta.days'));
        $this->assertEquals(1, $response->json('meta.count'));

        // Request with 20 days
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/expiring-soon?days=20');

        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.count'));
    }

    public function test_tech_can_get_expiring_quotes(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(3),
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->getJson('/api/v1/quotes/expiring-soon');

        $response->assertOk();
    }

    public function test_guest_cannot_get_expiring_quotes(): void
    {
        $response = $this->getJson('/api/v1/quotes/expiring-soon');

        $response->assertStatus(401);
    }

    public function test_excludes_expired_quotes(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        // Already expired quote
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->subDays(1),
        ]);

        // Quote expiring soon
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(3),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/expiring-soon');

        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.count'));
    }

    public function test_results_ordered_by_valid_until(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(5),
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(2),
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'valid_until' => now()->addDays(6),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/expiring-soon');

        $response->assertOk();

        $data = $response->json('data');

        // First should be expiring soonest (2 days)
        $firstValidUntil = $data[0]['attributes']['validUntil'];
        $lastValidUntil = $data[count($data) - 1]['attributes']['validUntil'];

        $this->assertLessThan($lastValidUntil, $firstValidUntil);
    }

    public function test_returns_empty_when_no_expiring_quotes(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/expiring-soon');

        $response->assertOk();
        $this->assertEquals(0, $response->json('meta.count'));
        $this->assertEmpty($response->json('data'));
    }
}

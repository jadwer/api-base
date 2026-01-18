<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteSummaryTest extends TestCase
{
    public function test_admin_can_get_quote_summary(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        // Create quotes with different statuses
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
            'total_amount' => 1000,
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'total_amount' => 2000,
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'total_amount' => 3000,
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'converted',
            'total_amount' => 4000,
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'rejected',
            'total_amount' => 500,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/summary');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'total',
                'draft',
                'sent',
                'accepted',
                'converted',
                'rejected',
                'expired',
                'cancelled',
                'totalValue',
                'averageValue',
                'conversionRate',
            ],
        ]);

        $this->assertEquals(5, $response->json('data.total'));
        $this->assertEquals(1, $response->json('data.draft'));
        $this->assertEquals(1, $response->json('data.sent'));
        $this->assertEquals(1, $response->json('data.accepted'));
        $this->assertEquals(1, $response->json('data.converted'));
        $this->assertEquals(1, $response->json('data.rejected'));
    }

    public function test_tech_can_get_quote_summary(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->getJson('/api/v1/quotes/summary');

        $response->assertOk();
    }

    public function test_guest_cannot_get_quote_summary(): void
    {
        $response = $this->getJson('/api/v1/quotes/summary');

        $response->assertStatus(401);
    }

    public function test_calculates_conversion_rate(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        // 2 converted out of 4 total = 50% conversion rate
        Quote::factory()->count(2)->create([
            'contact_id' => $contact->id,
            'status' => 'converted',
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/summary');

        $response->assertOk();
        $this->assertEquals(50, $response->json('data.conversionRate'));
    }

    public function test_returns_zero_conversion_rate_when_no_quotes(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/summary');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total'));
        $this->assertEquals(0, $response->json('data.conversionRate'));
    }

    public function test_calculates_total_and_average_value(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        // Active quotes (draft, sent, accepted) for value calculation
        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
            'total_amount' => 1000,
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'total_amount' => 2000,
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'total_amount' => 3000,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/summary');

        $response->assertOk();

        // Total value: 1000 + 2000 + 3000 = 6000
        $this->assertEquals(6000, $response->json('data.totalValue'));

        // Average value: 6000 / 3 = 2000
        $this->assertEquals(2000, $response->json('data.averageValue'));
    }

    public function test_counts_expired_quotes(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'expired',
        ]);

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'expired',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/summary');

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.expired'));
    }

    public function test_counts_cancelled_quotes(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();

        Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes/summary');

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.cancelled'));
    }
}

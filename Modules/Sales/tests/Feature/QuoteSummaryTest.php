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

    /** VEN-06 (2026-11-22): bandeja por asignar. */
    public function test_la_bandeja_por_asignar_cuenta_y_filtra_las_abiertas_sin_vendedor(): void
    {
        $admin = $this->getAdminUser();
        $seller = \Modules\User\Models\User::factory()->create();
        $contact = \Modules\Contacts\Models\Contact::factory()->customer()->create();
        $base = ['contact_id' => $contact->id];
        \Modules\Sales\Models\Quote::query()->update(['assigned_to' => $seller->id]);

        $open = \Modules\Sales\Models\Quote::factory()->create($base + ['status' => 'sent']);
        $closed = \Modules\Sales\Models\Quote::factory()->create($base + ['status' => 'rejected']);
        $assigned = \Modules\Sales\Models\Quote::factory()->create($base + ['status' => 'sent', 'assigned_to' => $seller->id]);
        \Modules\Sales\Models\Quote::query()->whereKey([$open->id, $closed->id])->update(['assigned_to' => null]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/quotes/summary')
            ->assertOk()
            ->assertJsonPath('data.unassigned', 1);

        $ids = collect($this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/quotes?filter[unassigned]=true', ['Accept' => 'application/vnd.api+json'])
            ->assertOk()
            ->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([$open->id], $ids);
        $this->assertNotContains($assigned->id, $ids);
    }
}

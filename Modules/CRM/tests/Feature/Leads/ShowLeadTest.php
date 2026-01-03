<?php

namespace Modules\CRM\Tests\Feature\Leads;

use Tests\TestCase;
use Modules\CRM\Models\Lead;
use Modules\User\Models\User;

class ShowLeadTest extends TestCase
{
    public function test_admin_can_view_lead(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'leads',
                'id' => (string) $lead->id,
                'attributes' => [
                    'title' => $lead->title,
                    'status' => $lead->status,
                    'rating' => $lead->rating,
                ],
            ],
        ]);
    }

    public function test_admin_can_view_lead_with_all_fields(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->qualified()->create([
            'user_id' => $user->id,
            'source' => 'website',
            'company_name' => 'Acme Corporation',
            'estimated_value' => 50000.00,
            'metadata' => [
                'industry' => 'Technology',
                'company_size' => '51-200',
            ],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'leads',
                'id' => (string) $lead->id,
                'attributes' => [
                    'source' => 'website',
                    'companyName' => 'Acme Corporation',
                    'estimatedValue' => 50000.00,
                    'metadata' => [
                        'industry' => 'Technology',
                        'company_size' => '51-200',
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_can_view_lead_with_contact_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();
        $contact = \Modules\Contacts\Models\Contact::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}?include=contact");

        $response->assertOk();
        $response->assertJsonStructure(['included']);
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_admin_can_view_lead_with_user_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}?include=user");

        $response->assertOk();
        $response->assertJsonStructure(['included']);
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_tech_user_can_view_lead(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_lead(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_lead(): void
    {
        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_lead(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads/99999');

        $response->assertStatus(404);
    }

    public function test_can_view_converted_lead(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->converted()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'status' => 'converted',
                ],
            ],
        ]);
        $this->assertNotNull($response->json('data.attributes.convertedAt'));
    }

    public function test_can_view_lead_without_contact(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->withoutContact()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $this->assertNull($lead->contact_id);
    }
}

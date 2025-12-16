<?php

namespace Modules\CRM\Tests\Feature\Opportunities;

use Tests\TestCase;
use Modules\CRM\Models\Opportunity;
use Modules\User\Models\User;

class ShowOpportunityTest extends TestCase
{
    public function test_admin_can_view_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'opportunities',
                'id' => (string) $opportunity->id,
                'attributes' => [
                    'name' => $opportunity->name,
                    'status' => $opportunity->status,
                    'stage' => $opportunity->stage,
                ],
            ],
        ]);
    }

    public function test_admin_can_view_opportunity_with_all_fields(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->highValue()->create([
            'user_id' => $user->id,
            'name' => 'Enterprise Deal',
            'amount' => 500000.00,
            'probability' => 80,
            'forecast_category' => 'commit',
            'metadata' => [
                'industry' => 'Technology',
                'decision_makers' => ['John Doe', 'Jane Smith'],
            ],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'opportunities',
                'id' => (string) $opportunity->id,
                'attributes' => [
                    'name' => 'Enterprise Deal',
                    'amount' => 500000.00,
                    'probability' => 80,
                    'forecastCategory' => 'commit',
                    'metadata' => [
                        'industry' => 'Technology',
                        'decision_makers' => ['John Doe', 'Jane Smith'],
                    ],
                ],
            ],
        ]);
    }

    public function test_expected_revenue_is_included_in_response(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
            'amount' => 100000.00,
            'probability' => 50,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'expectedRevenue' => 50000.00,
                ],
            ],
        ]);
    }

    public function test_admin_can_view_opportunity_with_user_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}?include=user");

        $response->assertOk();
        $response->assertJsonStructure(['included']);
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_tech_user_can_view_opportunity(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_opportunity(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_opportunity(): void
    {
        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities/99999');

        $response->assertStatus(404);
    }

    public function test_can_view_won_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->won()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'status' => 'won',
                ],
            ],
        ]);
        $this->assertNotNull($response->json('data.attributes.wonAt'));
    }

    public function test_can_view_lost_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->lost()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'status' => 'lost',
                ],
            ],
        ]);
        $this->assertNotNull($response->json('data.attributes.lostAt'));
    }
}

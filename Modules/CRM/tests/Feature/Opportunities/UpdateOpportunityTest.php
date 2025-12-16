<?php

namespace Modules\CRM\Tests\Feature\Opportunities;

use Tests\TestCase;
use Modules\CRM\Models\Opportunity;
use Modules\User\Models\User;

class UpdateOpportunityTest extends TestCase
{
    public function test_admin_can_update_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'opportunities',
                'id' => (string) $opportunity->id,
                'attributes' => [
                    'name' => 'Updated Name',
                ],
            ],
        ]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_update_opportunity_status(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->open()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'status' => 'won',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'status' => 'won',
        ]);

        // Verify won_at timestamp was set automatically
        $opportunity->refresh();
        $this->assertNotNull($opportunity->won_at);
    }

    public function test_admin_can_update_opportunity_amount_and_probability(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
            'amount' => 50000.00,
            'probability' => 50,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'amount' => 100000.00,
                'probability' => 75,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'amount' => 100000.00,
            'probability' => 75,
        ]);

        // Verify expected_revenue was recalculated
        $opportunity->refresh();
        $this->assertEquals(75000.00, $opportunity->expected_revenue);
    }

    public function test_admin_can_update_opportunity_metadata(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
            'metadata' => [
                'priority' => 'low',
            ],
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'metadata' => [
                    'priority' => 'high',
                    'notes' => 'Hot deal - needs immediate attention',
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();

        $opportunity->refresh();
        $this->assertEquals('high', $opportunity->metadata['priority']);
        $this->assertEquals('Hot deal - needs immediate attention', $opportunity->metadata['notes']);
    }

    public function test_admin_can_assign_opportunity_to_different_user(): void
    {
        $admin = $this->getAdminUser();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user1->id,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user2->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertOk();
        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'user_id' => $user2->id,
        ]);
    }

    public function test_status_must_be_valid_enum_when_updating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'status' => 'invalid-status',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/status']);
    }

    public function test_probability_must_be_between_0_and_100_when_updating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'probability' => 150,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/probability']);
    }

    public function test_tech_user_cannot_update_opportunity(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(403);
    }

    public function test_customer_user_cannot_update_opportunity(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_opportunity(): void
    {
        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity->id,
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_when_updating_nonexistent_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'opportunities',
            'id' => '99999',
            'attributes' => [
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch('/api/v1/opportunities/99999');

        $response->assertStatus(404);
    }

    public function test_updating_opportunity_does_not_affect_other_opportunities(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity1 = Opportunity::factory()->create([
            'name' => 'Opportunity 1',
            'user_id' => $user->id,
        ]);
        $opportunity2 = Opportunity::factory()->create([
            'name' => 'Opportunity 2',
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'opportunities',
            'id' => (string) $opportunity1->id,
            'attributes' => [
                'name' => 'Updated Opportunity 1',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->patch("/api/v1/opportunities/{$opportunity1->id}");

        $response->assertOk();
        $this->assertDatabaseHas('opportunities', ['id' => $opportunity1->id, 'name' => 'Updated Opportunity 1']);
        $this->assertDatabaseHas('opportunities', ['id' => $opportunity2->id, 'name' => 'Opportunity 2']);
    }
}

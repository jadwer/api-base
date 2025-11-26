<?php

namespace Modules\CRM\Tests\Feature\Leads;

use Tests\TestCase;
use Modules\CRM\Models\Lead;
use Modules\User\Models\User;

class UpdateLeadTest extends TestCase
{
    public function test_admin_can_update_lead(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'title' => 'Updated Title',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'leads',
                'id' => (string) $lead->id,
                'attributes' => [
                    'title' => 'Updated Title',
                ],
            ],
        ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_admin_can_update_lead_status(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->statusNew()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'status' => 'qualified',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'qualified',
        ]);
    }

    public function test_admin_can_update_lead_rating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->warm()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'rating' => 'hot',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'rating' => 'hot',
        ]);
    }

    public function test_admin_can_update_lead_estimated_value(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
            'estimated_value' => 10000.00,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'estimatedValue' => 50000.00,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'estimated_value' => 50000.00,
        ]);
    }

    public function test_admin_can_update_lead_metadata(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
            'metadata' => [
                'industry' => 'Technology',
            ],
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'metadata' => [
                    'industry' => 'Healthcare',
                    'priority' => 'high',
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertOk();

        $lead->refresh();
        $this->assertEquals('Healthcare', $lead->metadata['industry']);
        $this->assertEquals('high', $lead->metadata['priority']);
    }

    public function test_admin_can_assign_lead_to_different_user(): void
    {
        $admin = $this->getAdminUser();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user1->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
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
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertOk();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'user_id' => $user2->id,
        ]);
    }

    public function test_status_must_be_valid_enum_when_updating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'status' => 'invalid-status',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/status']);
    }

    public function test_rating_must_be_valid_enum_when_updating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'rating' => 'invalid-rating',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/rating']);
    }

    public function test_tech_user_cannot_update_lead(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'title' => 'Updated Title',
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertStatus(403);
    }

    public function test_customer_user_cannot_update_lead(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'title' => 'Updated Title',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_lead(): void
    {
        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead->id,
            'attributes' => [
                'title' => 'Updated Title',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_when_updating_nonexistent_lead(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leads',
            'id' => '99999',
            'attributes' => [
                'title' => 'Updated Title',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch('/api/v1/leads/99999');

        $response->assertStatus(404);
    }

    public function test_updating_lead_does_not_affect_other_leads(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead1 = Lead::factory()->create([
            'title' => 'Lead 1',
            'user_id' => $user->id,
        ]);
        $lead2 = Lead::factory()->create([
            'title' => 'Lead 2',
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'leads',
            'id' => (string) $lead1->id,
            'attributes' => [
                'title' => 'Updated Lead 1',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->patch("/api/v1/leads/{$lead1->id}");

        $response->assertOk();
        $this->assertDatabaseHas('leads', ['id' => $lead1->id, 'title' => 'Updated Lead 1']);
        $this->assertDatabaseHas('leads', ['id' => $lead2->id, 'title' => 'Lead 2']);
    }
}

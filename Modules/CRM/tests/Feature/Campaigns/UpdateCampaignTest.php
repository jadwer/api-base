<?php

namespace Modules\CRM\Tests\Feature\Campaigns;

use Tests\TestCase;
use Modules\CRM\Models\Campaign;
use Modules\User\Models\User;

class UpdateCampaignTest extends TestCase
{
    public function test_admin_can_update_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Campaign',
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'name' => 'Updated Campaign',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'campaigns',
                'id' => (string) $campaign->id,
                'attributes' => [
                    'name' => 'Updated Campaign',
                ],
            ],
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign',
        ]);
    }

    public function test_admin_can_update_campaign_status(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->planning()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'status' => 'active',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_campaign_type(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->email()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'type' => 'social_media',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'type' => 'social_media',
        ]);
    }

    public function test_admin_can_update_campaign_budget(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'budget' => 50000.00,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'budget' => 75000.00,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'budget' => 75000.00,
        ]);
    }

    public function test_admin_can_update_campaign_financial_fields(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->active()->create([
            'user_id' => $user->id,
            'budget' => 100000.00,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'actualCost' => 95000.00,
                'expectedRevenue' => 250000.00,
                'actualRevenue' => 280000.00,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'actual_cost' => 95000.00,
            'expected_revenue' => 250000.00,
            'actual_revenue' => 280000.00,
        ]);
    }

    public function test_admin_can_update_campaign_dates(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'start_date' => now(),
        ]);

        $newStartDate = now()->addMonth()->format('Y-m-d');
        $newEndDate = now()->addMonths(3)->format('Y-m-d');

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'startDate' => $newStartDate,
                'endDate' => $newEndDate,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'start_date' => $newStartDate,
            'endDate' => $newEndDate,
        ]);
    }

    public function test_admin_can_update_campaign_metadata(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'metadata' => [
                'platform' => 'Email',
            ],
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'metadata' => [
                    'platform' => 'Social Media',
                    'channels' => ['Facebook', 'LinkedIn', 'Twitter'],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();

        $campaign->refresh();
        $this->assertEquals('Social Media', $campaign->metadata['platform']);
        $this->assertEquals(['Facebook', 'LinkedIn', 'Twitter'], $campaign->metadata['channels']);
    }

    public function test_admin_can_assign_campaign_to_different_user(): void
    {
        $admin = $this->getAdminUser();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user1->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
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
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'user_id' => $user2->id,
        ]);
    }

    public function test_status_must_be_valid_enum_when_updating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'status' => 'invalid-status',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_type_must_be_valid_enum_when_updating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'type' => 'invalid-type',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_end_date_must_be_after_or_equal_start_date_when_updating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'startDate' => now()->format('Y-m-d'),
                'endDate' => now()->subDay()->format('Y-m-d'),
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['endDate']);
    }

    public function test_tech_user_cannot_update_campaign(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'name' => 'Updated Campaign',
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
    }

    public function test_customer_user_cannot_update_campaign(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'name' => 'Updated Campaign',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_campaign(): void
    {
        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign->id,
            'attributes' => [
                'name' => 'Updated Campaign',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_when_updating_nonexistent_campaign(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'campaigns',
            'id' => '99999',
            'attributes' => [
                'name' => 'Updated Campaign',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch('/api/v1/campaigns/99999');

        $response->assertStatus(404);
    }

    public function test_updating_campaign_does_not_affect_other_campaigns(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign1 = Campaign::factory()->create([
            'name' => 'Campaign 1',
            'user_id' => $user->id,
        ]);
        $campaign2 = Campaign::factory()->create([
            'name' => 'Campaign 2',
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'campaigns',
            'id' => (string) $campaign1->id,
            'attributes' => [
                'name' => 'Updated Campaign 1',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->patch("/api/v1/campaigns/{$campaign1->id}");

        $response->assertOk();
        $this->assertDatabaseHas('campaigns', ['id' => $campaign1->id, 'name' => 'Updated Campaign 1']);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign2->id, 'name' => 'Campaign 2']);
    }
}

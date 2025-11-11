<?php

namespace Modules\CRM\Tests\Feature\Campaigns;

use Tests\TestCase;
use Modules\CRM\Models\Campaign;
use Modules\User\Models\User;

class StoreCampaignTest extends TestCase
{
    public function test_admin_can_create_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Q4 2024 Email Campaign',
                'campaignType' => 'email',
                'status' => 'planning',
                'startDate' => now()->addWeek()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'campaigns',
                'attributes' => [
                    'name' => 'Q4 2024 Email Campaign',
                    'campaignType' => 'email',
                    'status' => 'planning',
                ],
            ],
        ]);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Q4 2024 Email Campaign',
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_can_create_campaign_with_all_fields(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Complete Campaign',
                'campaignType' => 'social_media',
                'status' => 'active',
                'startDate' => now()->format('Y-m-d'),
                'endDate' => now()->addMonth()->format('Y-m-d'),
                'budget' => 50000.00,
                'expectedRevenue' => 150000.00,
                'targetAudience' => 'Tech startups',
                'description' => 'Comprehensive social media campaign targeting tech startups',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertCreated();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Complete Campaign',
            'target_audience' => 'Tech startups',
            'budget' => 50000.00,
        ]);
    }

    public function test_admin_can_create_campaign_with_metadata(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Campaign with Metadata',
                'campaignType' => 'event',
                'startDate' => now()->format('Y-m-d'),
                'metadata' => [
                    'platform' => 'Event',
                    'venue' => 'Convention Center',
                    'expected_attendees' => 500,
                ],
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertCreated();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Campaign with Metadata',
        ]);

        $campaign = Campaign::where('name', 'Campaign with Metadata')->first();
        $this->assertEquals('Convention Center', $campaign->metadata['venue']);
    }

    public function test_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_type_is_required(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'startDate' => now()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['campaignType']);
    }

    public function test_type_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'invalid-type',
                'startDate' => now()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['campaignType']);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'status' => 'invalid-status',
                'startDate' => now()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_user_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    public function test_start_date_is_required(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['startDate']);
    }

    public function test_end_date_must_be_after_or_equal_start_date(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
                'endDate' => now()->subDay()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['endDate']);
    }

    public function test_budget_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
                'budget' => 'not-a-number',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['budget']);
    }

    public function test_budget_cannot_be_negative(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
                'budget' => -5000,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['budget']);
    }

    public function test_expected_revenue_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
                'expectedRevenue' => 'not-a-number',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expectedRevenue']);
    }

    public function test_tech_user_cannot_create_campaign(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(403);
    }

    public function test_customer_user_cannot_create_campaign(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_campaign(): void
    {
        $user = User::factory()->create();

        $data = [
            'type' => 'campaigns',
            'attributes' => [
                'name' => 'Test Campaign',
                'campaignType' => 'email',
                'startDate' => now()->format('Y-m-d'),
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('campaigns')
            ->withData($data)
            ->post('/api/v1/campaigns');

        $response->assertStatus(401);
    }
}

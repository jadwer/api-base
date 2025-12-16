<?php

namespace Modules\CRM\Tests\Feature\Opportunities;

use Tests\TestCase;
use Modules\CRM\Models\Opportunity;
use Modules\User\Models\User;

class StoreOpportunityTest extends TestCase
{
    public function test_admin_can_create_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Enterprise Software Deal',
                'description' => 'Major software licensing opportunity',
                'amount' => 150000.00,
                'probability' => 75,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'commit',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'opportunities',
                'attributes' => [
                    'name' => 'Enterprise Software Deal',
                    'amount' => 150000.00,
                    'probability' => 75,
                    'status' => 'open',
                    'stage' => 'proposal',
                ],
            ],
        ]);

        $this->assertDatabaseHas('opportunities', [
            'name' => 'Enterprise Software Deal',
            'user_id' => $user->id,
            'amount' => 150000.00,
            'probability' => 75,
        ]);
    }

    public function test_expected_revenue_is_auto_calculated(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 100000.00,
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'best_case',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertCreated();

        $opportunity = Opportunity::where('name', 'Test Opportunity')->first();
        $this->assertEquals(50000.00, $opportunity->expected_revenue);
        $this->assertEquals(50000.00, $response->json('data.attributes.expectedRevenue'));
    }

    public function test_admin_can_create_opportunity_with_metadata(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Deal with Metadata',
                'amount' => 75000.00,
                'probability' => 60,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
                'metadata' => [
                    'decision_makers' => ['John Doe', 'Jane Smith'],
                    'competitors' => ['Competitor A', 'Competitor B'],
                    'key_requirements' => ['Feature X', 'Feature Y'],
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertCreated();
        $this->assertDatabaseHas('opportunities', [
            'name' => 'Deal with Metadata',
        ]);

        $opportunity = Opportunity::where('name', 'Deal with Metadata')->first();
        $this->assertEquals(['John Doe', 'Jane Smith'], $opportunity->metadata['decision_makers']);
    }

    public function test_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'amount' => 50000.00,
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/name']);
    }

    public function test_amount_is_required(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/amount']);
    }

    public function test_probability_is_required(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 50000.00,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/probability']);
    }

    public function test_probability_must_be_between_0_and_100(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 50000.00,
                'probability' => 150,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/probability']);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 50000.00,
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'invalid-status',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/status']);
    }

    public function test_forecast_category_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 50000.00,
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'invalid-category',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/attributes/forecastCategory']);
    }

    public function test_user_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 50000.00,
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(422);
        $response->assertJsonFragment(['pointer' => '/data/relationships/user']);
    }

    public function test_tech_user_cannot_create_opportunity(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 50000.00,
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(403);
    }

    public function test_customer_user_cannot_create_opportunity(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 50000.00,
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_opportunity(): void
    {
        $user = User::factory()->create();

        $data = [
            'type' => 'opportunities',
            'attributes' => [
                'name' => 'Test Opportunity',
                'amount' => 50000.00,
                'probability' => 50,
                'closeDate' => '2025-12-31',
                'status' => 'open',
                'stage' => 'proposal',
                'forecastCategory' => 'pipeline',
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
            ->expects('opportunities')
            ->withData($data)
            ->post('/api/v1/opportunities');

        $response->assertStatus(401);
    }
}

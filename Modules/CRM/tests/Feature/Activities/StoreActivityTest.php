<?php

namespace Modules\CRM\Tests\Feature\Activities;

use Tests\TestCase;
use Modules\CRM\Models\Activity;
use Modules\CRM\Models\Lead;
use Modules\User\Models\User;

class StoreActivityTest extends TestCase
{
    public function test_admin_can_create_activity(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        $data = [
            'type' => 'activities',
            'attributes' => [
                'activityType' => 'call',
                'subject' => 'Follow-up call',
                'description' => 'Call to discuss proposal',
                'status' => 'pending',
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
            ->expects('activities')
            ->withData($data)
            ->post('/api/v1/activities');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'activities',
                'attributes' => [
                    'activityType' => 'call',
                    'subject' => 'Follow-up call',
                ],
            ],
        ]);

        $this->assertDatabaseHas('activities', [
            'subject' => 'Follow-up call',
            'activity_type' => 'call',
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_can_create_activity_with_lead(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['user_id' => $user->id]);

        $data = [
            'type' => 'activities',
            'attributes' => [
                'activityType' => 'meeting',
                'subject' => 'Demo meeting',
                'status' => 'scheduled',
            ],
            'relationships' => [
                'user' => [
                    'data' => ['type' => 'users', 'id' => (string) $user->id],
                ],
                'lead' => [
                    'data' => ['type' => 'leads', 'id' => (string) $lead->id],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->post('/api/v1/activities');

        $response->assertCreated();
        $this->assertDatabaseHas('activities', [
            'lead_id' => $lead->id,
        ]);
    }

    public function test_validation_error_when_required_fields_missing(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'activities',
            'attributes' => [
                'activityType' => 'call',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->post('/api/v1/activities');

        $response->assertStatus(422);
    }

    public function test_tech_user_cannot_create_activity(): void
    {
        $tech = $this->getTechUser();
        $user = User::factory()->create();

        $data = [
            'type' => 'activities',
            'attributes' => [
                'activityType' => 'email',
                'subject' => 'Welcome email',
                'status' => 'completed',
            ],
            'relationships' => [
                'user' => [
                    'data' => ['type' => 'users', 'id' => (string) $user->id],
                ],
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->post('/api/v1/activities');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_activity(): void
    {
        $user = User::factory()->create();

        $data = [
            'type' => 'activities',
            'attributes' => [
                'activityType' => 'note',
                'subject' => 'Test note',
                'status' => 'pending',
            ],
            'relationships' => [
                'user' => [
                    'data' => ['type' => 'users', 'id' => (string) $user->id],
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->post('/api/v1/activities');

        $response->assertStatus(401);
    }
}

<?php

namespace Modules\CRM\Tests\Feature\Activities;

use Tests\TestCase;
use Modules\CRM\Models\Activity;
use Modules\User\Models\User;

class UpdateActivityTest extends TestCase
{
    public function test_admin_can_update_activity(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'subject' => 'Original Subject',
            'status' => 'pending',
        ]);

        $data = [
            'type' => 'activities',
            'id' => (string) $activity->id,
            'attributes' => [
                'subject' => 'Updated Subject',
                'status' => 'completed',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->patch('/api/v1/activities/' . $activity->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'activities',
                'id' => (string) $activity->id,
                'attributes' => [
                    'subject' => 'Updated Subject',
                    'status' => 'completed',
                ],
            ],
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'subject' => 'Updated Subject',
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_update_activity_outcome(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'outcome' => null,
        ]);

        $data = [
            'type' => 'activities',
            'id' => (string) $activity->id,
            'attributes' => [
                'outcome' => 'successful',
                'status' => 'completed',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->patch('/api/v1/activities/' . $activity->id);

        $response->assertOk();
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'outcome' => 'successful',
        ]);
    }

    public function test_tech_user_can_update_activity(): void
    {
        $tech = $this->getTechUser();
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'subject' => 'Original',
        ]);

        $data = [
            'type' => 'activities',
            'id' => (string) $activity->id,
            'attributes' => [
                'subject' => 'Modified',
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->patch('/api/v1/activities/' . $activity->id);

        $response->assertOk();
    }

    public function test_guest_cannot_update_activity(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'type' => 'activities',
            'id' => (string) $activity->id,
            'attributes' => [
                'subject' => 'Hacked',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->patch('/api/v1/activities/' . $activity->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_when_updating_non_existent_activity(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'activities',
            'id' => '99999',
            'attributes' => [
                'subject' => 'Test',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->withData($data)
            ->patch('/api/v1/activities/99999');

        $response->assertStatus(404);
    }
}

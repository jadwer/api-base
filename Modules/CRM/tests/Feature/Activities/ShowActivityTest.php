<?php

namespace Modules\CRM\Tests\Feature\Activities;

use Tests\TestCase;
use Modules\CRM\Models\Activity;
use Modules\User\Models\User;

class ShowActivityTest extends TestCase
{
    public function test_admin_can_view_single_activity(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'subject' => 'Test Activity Subject',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities/' . $activity->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'activities',
                'id' => (string) $activity->id,
                'attributes' => [
                    'subject' => 'Test Activity Subject',
                ],
            ],
        ]);
    }

    public function test_admin_can_view_activity_with_relationships(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->includePaths('user', 'lead')
            ->get('/api/v1/activities/' . $activity->id);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'user',
                ],
            ],
        ]);
    }

    public function test_tech_user_can_view_activity(): void
    {
        $tech = $this->getTechUser();
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities/' . $activity->id);

        $response->assertOk();
    }

    public function test_guest_cannot_view_activity(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities/' . $activity->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_non_existent_activity(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities/99999');

        $response->assertStatus(404);
    }
}

<?php

namespace Modules\CRM\Tests\Feature\Activities;

use Tests\TestCase;
use Modules\CRM\Models\Activity;
use Modules\User\Models\User;

class DestroyActivityTest extends TestCase
{
    public function test_admin_can_delete_activity(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->delete('/api/v1/activities/' . $activity->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('activities', [
            'id' => $activity->id,
        ]);
    }

    public function test_tech_user_can_delete_activity(): void
    {
        $tech = $this->getTechUser();
        $user = User::factory()->create();

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->delete('/api/v1/activities/' . $activity->id);

        $response->assertNoContent();
    }

    public function test_guest_cannot_delete_activity(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('activities')
            ->delete('/api/v1/activities/' . $activity->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_when_deleting_non_existent_activity(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->delete('/api/v1/activities/99999');

        $response->assertStatus(404);
    }

    public function test_can_delete_activity_without_affecting_related_lead(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();
        $lead = \Modules\CRM\Models\Lead::factory()->create(['user_id' => $user->id]);

        $activity = Activity::factory()->create([
            'user_id' => $user->id,
            'lead_id' => $lead->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->delete('/api/v1/activities/' . $activity->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]); // Lead still exists
    }
}

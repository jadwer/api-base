<?php

namespace Modules\CRM\Tests\Feature\Activities;

use Tests\TestCase;
use Modules\CRM\Models\Activity;
use Modules\CRM\Models\Lead;
use Modules\User\Models\User;

class IndexActivitiesTest extends TestCase
{
    public function test_admin_can_list_activities(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        Activity::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_list_activities_with_pagination(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        Activity::factory()->count(25)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities?page[size]=10&page[number]=1');

        $response->assertOk();
        $this->assertEquals(10, count($response->json('data')));
        $response->assertJsonStructure(['meta' => ['page']]);
    }

    public function test_admin_can_filter_activities_by_type(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        Activity::factory()->count(2)->call()->create([
            'user_id' => $user->id,
        ]);
        Activity::factory()->count(1)->email()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities?filter[activityType]=call');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        foreach ($response->json('data') as $activity) {
            $this->assertEquals('call', $activity['attributes']['activityType']);
        }
    }

    public function test_admin_can_filter_activities_by_status(): void
    {
        $admin = $this->getAdminUser();
        $user = User::factory()->create();

        Activity::factory()->count(2)->completed()->create([
            'user_id' => $user->id,
        ]);
        Activity::factory()->count(1)->pending()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities?filter[status]=completed');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        foreach ($response->json('data') as $activity) {
            $this->assertEquals('completed', $activity['attributes']['status']);
        }
    }

    public function test_tech_user_can_list_activities(): void
    {
        $tech = $this->getTechUser();
        $user = User::factory()->create();

        Activity::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities');

        $response->assertOk();
    }

    public function test_guest_cannot_list_activities(): void
    {
        $response = $this->jsonApi()
            ->expects('activities')
            ->get('/api/v1/activities');

        $response->assertStatus(401);
    }
}

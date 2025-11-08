<?php

namespace Modules\CRM\Tests\Feature\Campaigns;

use Tests\TestCase;
use Modules\CRM\Models\Campaign;
use Modules\User\Models\User;

class IndexCampaignsTest extends TestCase
{
    public function test_admin_can_list_campaigns(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Campaign::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_list_campaigns_with_pagination(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Campaign::factory()->count(25)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns?page[size]=10&page[number]=1');

        $response->assertOk();
        $this->assertEquals(10, count($response->json('data')));
        $response->assertJsonStructure(['meta' => ['page']]);
    }

    public function test_admin_can_sort_campaigns_by_name(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Campaign::factory()->create([
            'name' => 'Alpha Campaign',
            'user_id' => $user->id,
        ]);
        Campaign::factory()->create([
            'name' => 'Beta Campaign',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertTrue($names->first() === 'Alpha Campaign' || $names->contains('Alpha Campaign'));
    }

    public function test_admin_can_sort_campaigns_by_start_date(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Campaign::factory()->create([
            'name' => 'Old Campaign',
            'start_date' => now()->subMonth(),
            'user_id' => $user->id,
        ]);
        Campaign::factory()->create([
            'name' => 'New Campaign',
            'start_date' => now(),
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns?sort=-startDate');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertTrue($names->first() === 'New Campaign' || $names->contains('New Campaign'));
    }

    public function test_admin_can_filter_campaigns_by_status(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Campaign::factory()->count(2)->active()->create([
            'user_id' => $user->id,
        ]);
        Campaign::factory()->count(1)->planning()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns?filter[status]=active');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        foreach ($response->json('data') as $campaign) {
            $this->assertEquals('active', $campaign['attributes']['status']);
        }
    }

    public function test_admin_can_filter_campaigns_by_type(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Campaign::factory()->count(2)->email()->create([
            'user_id' => $user->id,
        ]);
        Campaign::factory()->count(1)->socialMedia()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns?filter[type]=email');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        foreach ($response->json('data') as $campaign) {
            $this->assertEquals('email', $campaign['attributes']['type']);
        }
    }

    public function test_admin_can_filter_campaigns_by_user_id(): void
    {
        $admin = $this->getAdminUser();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Campaign::factory()->count(2)->create([
            'user_id' => $user1->id,
        ]);
        Campaign::factory()->count(1)->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns?filter[userId]={$user1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_include_user_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns?include=user&filter[id]={$campaign->id}");

        $response->assertOk();
        $response->assertJsonStructure(['included']);
    }

    public function test_admin_can_include_leads_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns?include=leads&filter[id]={$campaign->id}");

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_tech_user_can_list_campaigns(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        Campaign::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_campaigns(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_campaigns(): void
    {
        $response = $this->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns');

        $response->assertStatus(401);
    }

    public function test_empty_campaigns_list_returns_empty_array(): void
    {
        $admin = $this->getAdminUser();

        Campaign::query()->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns');

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }
}

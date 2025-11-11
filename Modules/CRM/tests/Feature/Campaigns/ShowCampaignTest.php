<?php

namespace Modules\CRM\Tests\Feature\Campaigns;

use Tests\TestCase;
use Modules\CRM\Models\Campaign;
use Modules\User\Models\User;

class ShowCampaignTest extends TestCase
{
    public function test_admin_can_view_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'campaigns',
                'id' => (string) $campaign->id,
                'attributes' => [
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'campaignType' => $campaign->type,
                ],
            ],
        ]);
    }

    public function test_admin_can_view_campaign_with_all_fields(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->completed()->create([
            'user_id' => $user->id,
            'target_audience' => 'Small business owners',
            'description' => 'Q4 2024 email campaign targeting SMBs',
            'budget' => 100000.00,
            'actual_cost' => 95000.00,
            'expected_revenue' => 250000.00,
            'actual_revenue' => 275000.00,
            'metadata' => [
                'platform' => 'Email',
                'email_provider' => 'Mailchimp',
                'total_recipients' => 10000,
            ],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'campaigns',
                'id' => (string) $campaign->id,
                'attributes' => [
                    'targetAudience' => 'Small business owners',
                    'description' => 'Q4 2024 email campaign targeting SMBs',
                    'budget' => 100000.00,
                    'actualCost' => 95000.00,
                    'expectedRevenue' => 250000.00,
                    'actualRevenue' => 275000.00,
                    'metadata' => [
                        'platform' => 'Email',
                        'email_provider' => 'Mailchimp',
                        'total_recipients' => 10000,
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_can_view_campaign_with_user_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}?include=user");

        $response->assertOk();
        $response->assertJsonStructure(['included']);
        $this->assertNotEmpty($response->json('included'));
    }

    public function test_admin_can_view_campaign_with_leads_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}?include=leads");

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_tech_user_can_view_campaign(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_campaign(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_campaign(): void
    {
        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_campaign(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get('/api/v1/campaigns/99999');

        $response->assertStatus(404);
    }

    public function test_can_view_active_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->active()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'status' => 'active',
                ],
            ],
        ]);
    }

    public function test_can_view_completed_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'status' => 'completed',
                ],
            ],
        ]);
        $this->assertNotNull($response->json('data.attributes.endDate'));
    }

    public function test_can_view_email_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->email()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'campaignType' => 'email',
                ],
            ],
        ]);
    }

    public function test_can_view_social_media_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->socialMedia()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->get("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'campaignType' => 'social_media',
                ],
            ],
        ]);
    }
}

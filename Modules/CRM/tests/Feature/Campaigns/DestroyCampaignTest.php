<?php

namespace Modules\CRM\Tests\Feature\Campaigns;

use Tests\TestCase;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Lead;
use Modules\User\Models\User;

class DestroyCampaignTest extends TestCase
{
    public function test_admin_can_delete_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('campaigns', [
            'id' => $campaign->id
        ]);
    }

    public function test_admin_can_delete_planning_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->planning()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_admin_can_delete_active_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->active()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_admin_can_delete_completed_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->completed()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_admin_can_delete_cancelled_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->cancelled()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_admin_can_delete_email_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->email()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_admin_can_delete_social_media_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->socialMedia()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_admin_can_delete_event_campaign(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->event()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_deleting_campaign_removes_pivot_table_entries(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $lead1 = Lead::factory()->create(['user_id' => $user->id]);
        $lead2 = Lead::factory()->create(['user_id' => $user->id]);

        // Attach leads to campaign
        $campaign->leads()->attach([$lead1->id, $lead2->id]);

        $this->assertDatabaseHas('campaign_lead', [
            'campaign_id' => $campaign->id,
            'lead_id' => $lead1->id,
        ]);
        $this->assertDatabaseHas('campaign_lead', [
            'campaign_id' => $campaign->id,
            'lead_id' => $lead2->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();

        // Campaign should be deleted
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);

        // Pivot entries should be deleted (cascade)
        $this->assertDatabaseMissing('campaign_lead', [
            'campaign_id' => $campaign->id,
        ]);

        // Leads should still exist
        $this->assertDatabaseHas('leads', ['id' => $lead1->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead2->id]);
    }

    public function test_tech_user_cannot_delete_campaign(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_customer_user_cannot_delete_campaign(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_guest_cannot_delete_campaign(): void
    {
        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_returns_404_for_nonexistent_campaign(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete('/api/v1/campaigns/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_campaign_does_not_affect_other_campaigns(): void
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

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign1->id]);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign2->id]);
    }

    public function test_deleting_campaign_does_not_delete_assigned_user(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('campaigns')
            ->delete("/api/v1/campaigns/{$campaign->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}

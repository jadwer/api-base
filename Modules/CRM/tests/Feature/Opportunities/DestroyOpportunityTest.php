<?php

namespace Modules\CRM\Tests\Feature\Opportunities;

use Tests\TestCase;
use Modules\CRM\Models\Opportunity;
use Modules\User\Models\User;

class DestroyOpportunityTest extends TestCase
{
    public function test_admin_can_delete_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('opportunities', [
            'id' => $opportunity->id
        ]);
    }

    public function test_admin_can_delete_open_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->open()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunity->id]);
    }

    public function test_admin_can_delete_won_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->won()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunity->id]);
    }

    public function test_admin_can_delete_lost_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->lost()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunity->id]);
    }

    public function test_admin_can_delete_high_value_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->highValue()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunity->id]);
    }

    public function test_admin_can_delete_opportunity_with_high_probability(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->highProbability()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunity->id]);
    }

    public function test_tech_user_cannot_delete_opportunity(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('opportunities', ['id' => $opportunity->id]);
    }

    public function test_customer_user_cannot_delete_opportunity(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('opportunities', ['id' => $opportunity->id]);
    }

    public function test_guest_cannot_delete_opportunity(): void
    {
        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('opportunities', ['id' => $opportunity->id]);
    }

    public function test_returns_404_for_nonexistent_opportunity(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete('/api/v1/opportunities/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_opportunity_does_not_affect_other_opportunities(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity1 = Opportunity::factory()->create([
            'name' => 'Opportunity 1',
            'user_id' => $user->id,
        ]);
        $opportunity2 = Opportunity::factory()->create([
            'name' => 'Opportunity 2',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunity1->id]);
        $this->assertDatabaseHas('opportunities', ['id' => $opportunity2->id]);
    }

    public function test_deleting_opportunity_does_not_delete_assigned_user(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunity->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_opportunity_does_not_delete_related_lead(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = \Modules\CRM\Models\Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
            'lead_id' => $lead->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->delete("/api/v1/opportunities/{$opportunity->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('opportunities', ['id' => $opportunity->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }
}

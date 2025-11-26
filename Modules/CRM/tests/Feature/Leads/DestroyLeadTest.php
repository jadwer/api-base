<?php

namespace Modules\CRM\Tests\Feature\Leads;

use Tests\TestCase;
use Modules\CRM\Models\Lead;
use Modules\User\Models\User;

class DestroyLeadTest extends TestCase
{
    public function test_admin_can_delete_lead(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('leads', [
            'id' => $lead->id
        ]);
    }

    public function test_admin_can_delete_new_lead(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->statusNew()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_delete_qualified_lead(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->qualified()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_delete_converted_lead(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->converted()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_delete_lead_with_hot_rating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->hot()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_delete_lead_without_contact(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->withoutContact()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_tech_user_cannot_delete_lead(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_customer_user_cannot_delete_lead(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_guest_cannot_delete_lead(): void
    {
        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_returns_404_for_nonexistent_lead(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete('/api/v1/leads/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_lead_does_not_affect_other_leads(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead1 = Lead::factory()->create([
            'title' => 'Lead 1',
            'user_id' => $user->id,
        ]);
        $lead2 = Lead::factory()->create([
            'title' => 'Lead 2',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leads', ['id' => $lead1->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead2->id]);
    }

    public function test_deleting_lead_does_not_delete_related_contact(): void
    {
        $this->markTestSkipped('Contact module not yet implemented');

        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_deleting_lead_does_not_delete_assigned_user(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->delete("/api/v1/leads/{$lead->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}

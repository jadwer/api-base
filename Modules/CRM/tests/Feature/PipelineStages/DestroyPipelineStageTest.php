<?php

namespace Modules\CRM\Tests\Feature\PipelineStages;

use Tests\TestCase;
use Modules\CRM\Models\PipelineStage;

class DestroyPipelineStageTest extends TestCase
{
    public function test_admin_can_delete_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('pipeline_stages', [
            'id' => $stage->id
        ]);
    }

    public function test_admin_can_delete_active_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->active()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_admin_can_delete_inactive_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_admin_can_delete_closed_won_stage(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->closedWon()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_admin_can_delete_lead_type_stage(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->forLeads()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_tech_user_cannot_delete_pipeline_stage(): void
    {
        $tech = $this->getTechUser();

        $stage = PipelineStage::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_customer_user_cannot_delete_pipeline_stage(): void
    {
        $customer = $this->getCustomerUser();

        $stage = PipelineStage::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_guest_cannot_delete_pipeline_stage(): void
    {
        $stage = PipelineStage::factory()->create();

        $response = $this->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_returns_404_for_nonexistent_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete('/api/v1/pipeline-stages/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_pipeline_stage_does_not_affect_other_stages(): void
    {
        $admin = $this->getAdminUser();

        $stage1 = PipelineStage::factory()->create(['name' => 'Stage 1']);
        $stage2 = PipelineStage::factory()->create(['name' => 'Stage 2']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$stage1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stage1->id]);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage2->id]);
    }

    public function test_can_delete_pipeline_stage_of_any_type(): void
    {
        $admin = $this->getAdminUser();

        $leadStage = PipelineStage::factory()->forLeads()->create(['name' => 'Lead Stage']);
        $opportunityStage = PipelineStage::factory()->forOpportunities()->create(['name' => 'Opportunity Stage']);

        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$leadStage->id}");

        $response1->assertNoContent();
        $this->assertDatabaseMissing('pipeline_stages', ['id' => $leadStage->id]);

        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->delete("/api/v1/pipeline-stages/{$opportunityStage->id}");

        $response2->assertNoContent();
        $this->assertDatabaseMissing('pipeline_stages', ['id' => $opportunityStage->id]);
    }
}

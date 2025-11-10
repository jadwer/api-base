<?php

namespace Modules\CRM\Tests\Feature\PipelineStages;

use Tests\TestCase;
use Modules\CRM\Models\PipelineStage;

class ShowPipelineStageTest extends TestCase
{
    public function test_admin_can_view_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->create([
            'name' => 'Prospecting',
            'type' => 'opportunity',
            'probability' => 25,
            'sort_order' => 1,
            'is_active' => true
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'type',
                    'probability',
                    'sortOrder',
                    'isActive',
                    'isClosedWon',
                    'isClosedLost'
                ]
            ]
        ]);
        $this->assertEquals($stage->id, $response->json('data.id'));
        $this->assertEquals('Prospecting', $response->json('data.attributes.name'));
        $this->assertEquals(25, $response->json('data.attributes.probability'));
    }

    public function test_tech_user_can_view_pipeline_stage(): void
    {
        $tech = $this->getTechUser();

        $stage = PipelineStage::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertEquals($stage->id, $response->json('data.id'));
    }

    public function test_customer_user_cannot_view_pipeline_stage(): void
    {
        $customer = $this->getCustomerUser();

        $stage = PipelineStage::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_pipeline_stage(): void
    {
        $stage = PipelineStage::factory()->create();

        $response = $this->jsonApi()
            ->expects('pipeline-stages')
            ->get("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages/99999');

        $response->assertStatus(404);
    }

    public function test_closed_won_stage_shows_correct_flags(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->closedWon()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertTrue($response->json('data.attributes.isClosedWon'));
        $this->assertFalse($response->json('data.attributes.isClosedLost'));
        $this->assertEquals(100, $response->json('data.attributes.probability'));
    }

    public function test_closed_lost_stage_shows_correct_flags(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->closedLost()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.isClosedWon'));
        $this->assertTrue($response->json('data.attributes.isClosedLost'));
        $this->assertEquals(0, $response->json('data.attributes.probability'));
    }

    public function test_inactive_stage_shows_correct_status(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }

    public function test_lead_type_stage_shows_correct_type(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->forLeads()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertEquals('lead', $response->json('data.attributes.type'));
    }
}

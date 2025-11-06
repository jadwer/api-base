<?php

namespace Modules\CRM\Tests\Feature\PipelineStages;

use Tests\TestCase;
use Modules\CRM\Models\PipelineStage;

class UpdatePipelineStageTest extends TestCase
{
    public function test_admin_can_update_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->create([
            'name' => 'Old Name',
            'probability' => 25
        ]);

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'name' => 'Updated Name',
                'probability' => 50,
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertEquals('Updated Name', $response->json('data.attributes.name'));
        $this->assertEquals(50, $response->json('data.attributes.probability'));
        $this->assertFalse($response->json('data.attributes.isActive'));

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'name' => 'Updated Name',
            'probability' => 50,
            'isActive' => false
        ]);
    }

    public function test_admin_can_change_stage_to_closed_won(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->create([
            'is_closed_won' => false,
            'is_closed_lost' => false,
            'probability' => 50
        ]);

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'isClosedWon' => true,
                'probability' => 100
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertTrue($response->json('data.attributes.isClosedWon'));
        $this->assertEquals(100, $response->json('data.attributes.probability'));
    }

    public function test_admin_can_deactivate_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->active()->create();

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }

    public function test_admin_can_change_sort_order(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->create(['sort_order' => 1]);

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'sortOrder' => 5
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.attributes.sortOrder'));
    }

    public function test_tech_user_cannot_update_pipeline_stage(): void
    {
        $tech = $this->getTechUser();

        $stage = PipelineStage::factory()->create();

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'name' => 'Should not update'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(403);
    }

    public function test_customer_user_cannot_update_pipeline_stage(): void
    {
        $customer = $this->getCustomerUser();

        $stage = PipelineStage::factory()->create();

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'name' => 'Should not update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_pipeline_stage(): void
    {
        $stage = PipelineStage::factory()->create();

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'name' => 'Should not update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'id' => '99999',
            'attributes' => [
                'name' => 'Should not work'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch('/api/v1/pipeline-stages/99999');

        $response->assertStatus(404);
    }

    public function test_can_update_only_specific_fields(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->create([
            'name' => 'Original Name',
            'type' => 'opportunity',
            'probability' => 25,
            'sort_order' => 1,
            'isActive' => true
        ]);

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'probability' => 50
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertOk();
        $this->assertEquals('Original Name', $response->json('data.attributes.name'));
        $this->assertEquals(50, $response->json('data.attributes.probability'));
        $this->assertTrue($response->json('data.attributes.isActive'));
    }

    public function test_probability_validation_applies_on_update(): void
    {
        $admin = $this->getAdminUser();

        $stage = PipelineStage::factory()->create();

        $data = [
            'type' => 'pipeline-stages',
            'id' => (string) $stage->id,
            'attributes' => [
                'probability' => 150 // Invalid: exceeds 100
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->patch("/api/v1/pipeline-stages/{$stage->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }
}

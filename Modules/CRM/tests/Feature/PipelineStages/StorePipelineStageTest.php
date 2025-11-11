<?php

namespace Modules\CRM\Tests\Feature\PipelineStages;

use Tests\TestCase;
use Modules\CRM\Models\PipelineStage;

class StorePipelineStageTest extends TestCase
{
    public function test_admin_can_create_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Prospecting',
                'stageType' => 'opportunity',
                'probability' => 25,
                'sortOrder' => 1,
                'isActive' => true,
                'isClosedWon' => false,
                'isClosedLost' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'type',
                    'probability',
                    'sortOrder',
                    'isActive'
                ]
            ]
        ]);

        $this->assertEquals('Prospecting', $response->json('data.attributes.name'));
        $this->assertEquals(25, $response->json('data.attributes.probability'));

        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Prospecting',
            'type' => 'opportunity',
            'probability' => 25
        ]);
    }

    public function test_admin_can_create_lead_pipeline_stage(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'New Lead',
                'stageType' => 'lead',
                'probability' => 10,
                'sortOrder' => 1,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertCreated();
        $this->assertEquals('lead', $response->json('data.attributes.type'));
    }

    public function test_admin_can_create_closed_won_stage(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Closed Won',
                'stageType' => 'opportunity',
                'probability' => 100,
                'sortOrder' => 5,
                'isActive' => true,
                'isClosedWon' => true,
                'isClosedLost' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertCreated();
        $this->assertTrue($response->json('data.attributes.isClosedWon'));
        $this->assertEquals(100, $response->json('data.attributes.probability'));
    }

    public function test_tech_user_cannot_create_pipeline_stage(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Should Not Create',
                'stageType' => 'opportunity',
                'probability' => 50,
                'sortOrder' => 1
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertStatus(403);
    }

    public function test_customer_user_cannot_create_pipeline_stage(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Should Not Create',
                'stageType' => 'opportunity',
                'probability' => 50,
                'sortOrder' => 1
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_pipeline_stage(): void
    {
        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Should Not Create',
                'stageType' => 'opportunity',
                'probability' => 50,
                'sortOrder' => 1
            ]
        ];

        $response = $this->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertStatus(401);
    }

    public function test_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'stageType' => 'opportunity',
                'probability' => 50,
                'sortOrder' => 1
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_type_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Test Stage',
                'probability' => 50,
                'sortOrder' => 1
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_type_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Test Stage',
                'stageType' => 'invalid_type',
                'probability' => 50,
                'sortOrder' => 1
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_probability_must_be_between_0_and_100(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Test Stage',
                'stageType' => 'opportunity',
                'probability' => 150,
                'sortOrder' => 1
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_sort_order_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'pipeline-stages',
            'attributes' => [
                'name' => 'Test Stage',
                'stageType' => 'opportunity',
                'probability' => 50
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->withData($data)
            ->post('/api/v1/pipeline-stages');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }
}

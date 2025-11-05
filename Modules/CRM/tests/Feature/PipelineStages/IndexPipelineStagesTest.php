<?php

namespace Modules\CRM\Tests\Feature\PipelineStages;

use Tests\TestCase;
use Modules\CRM\Models\PipelineStage;

class IndexPipelineStagesTest extends TestCase
{
    public function test_admin_can_list_pipeline_stages(): void
    {
        $admin = $this->getAdminUser();

        PipelineStage::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_pipeline_stages_by_sort_order(): void
    {
        $admin = $this->getAdminUser();

        PipelineStage::factory()->create(['name' => 'Last Stage', 'sort_order' => 10]);
        PipelineStage::factory()->create(['name' => 'First Stage', 'sort_order' => 1]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages?sort=sortOrder&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        $testStages = collect($data)->filter(function($item) {
            $name = $item['attributes']['name'] ?? null;
            return $name === 'First Stage' || $name === 'Last Stage';
        })->values();

        $this->assertCount(2, $testStages);
        $this->assertEquals('First Stage', $testStages[0]['attributes']['name']);
        $this->assertEquals('Last Stage', $testStages[1]['attributes']['name']);
    }

    public function test_admin_can_filter_pipeline_stages_by_type(): void
    {
        $admin = $this->getAdminUser();

        PipelineStage::factory()->count(2)->forLeads()->create();
        PipelineStage::factory()->count(1)->forOpportunities()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages?filter[type]=lead');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_pipeline_stages_by_active_status(): void
    {
        $admin = $this->getAdminUser();

        PipelineStage::factory()->count(2)->active()->create();
        PipelineStage::factory()->count(1)->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages?filter[isActive]=1');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_pipeline_stages_with_permission(): void
    {
        $tech = $this->getTechUser();

        PipelineStage::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_pipeline_stages(): void
    {
        $customer = $this->getCustomerUser();

        PipelineStage::factory()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_pipeline_stages(): void
    {
        $response = $this->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages');

        $response->assertStatus(401);
    }

    public function test_can_paginate_pipeline_stages(): void
    {
        $admin = $this->getAdminUser();

        PipelineStage::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    public function test_can_filter_pipeline_stages_by_closed_won(): void
    {
        $admin = $this->getAdminUser();

        PipelineStage::factory()->create(['name' => 'Won Stage', 'is_closed_won' => true]);
        PipelineStage::factory()->create(['name' => 'Active Stage', 'is_closed_won' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages?filter[isClosedWon]=1');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    public function test_can_filter_pipeline_stages_by_name(): void
    {
        $admin = $this->getAdminUser();

        PipelineStage::factory()->create(['name' => 'Prospecting']);
        PipelineStage::factory()->create(['name' => 'Qualification']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('pipeline-stages')
            ->get('/api/v1/pipeline-stages?filter[name]=Prospecting');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
    }
}

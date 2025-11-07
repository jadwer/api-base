<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Position;
use Modules\HR\Models\Department;

class PositionIndexTest extends TestCase
{
    public function test_admin_can_list_positions(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        Position::factory()->count(3)->create(['departmentId' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_positions_by_title(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        Position::factory()->create([
            'departmentId' => $department->id,
            'title' => 'Zulu Position'
        ]);
        Position::factory()->create([
            'departmentId' => $department->id,
            'title' => 'Alpha Position'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions?sort=title&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        $testPositions = collect($data)->filter(function($item) {
            $title = $item['attributes']['title'] ?? null;
            return $title === 'Alpha Position' || $title === 'Zulu Position';
        })->values();

        $this->assertCount(2, $testPositions);
        $this->assertEquals('Alpha Position', $testPositions[0]['attributes']['title']);
        $this->assertEquals('Zulu Position', $testPositions[1]['attributes']['title']);
    }

    public function test_admin_can_filter_positions_by_active_status(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        Position::factory()->count(2)->active()->create(['departmentId' => $department->id]);
        Position::factory()->count(1)->inactive()->create(['departmentId' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions?filter[isActive]=1');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_positions_by_department(): void
    {
        $admin = $this->getAdminUser();

        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        Position::factory()->count(2)->create(['departmentId' => $department1->id]);
        Position::factory()->count(1)->create(['departmentId' => $department2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get("/api/v1/positions?filter[department]={$department1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_positions_with_permission(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        Position::factory()->count(2)->create(['departmentId' => $department->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_positions(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        Position::factory()->count(2)->create(['departmentId' => $department->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_positions(): void
    {
        $response = $this->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions');

        $response->assertStatus(401);
    }

    public function test_can_paginate_positions(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        Position::factory()->count(25)->create(['departmentId' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    public function test_can_include_department_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        Position::factory()->create(['departmentId' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions?include=department');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'department'
                    ]
                ]
            ]
        ]);
    }
}

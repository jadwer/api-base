<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Position;

class DepartmentIndexTest extends TestCase
{
    public function test_admin_can_list_departments(): void
    {
        $admin = $this->getAdminUser();

        Department::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_departments_by_name(): void
    {
        $admin = $this->getAdminUser();

        Department::factory()->create(['name' => 'Zulu Department']);
        Department::factory()->create(['name' => 'Alpha Department']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments?sort=name&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        $testDepts = collect($data)->filter(function($item) {
            $name = $item['attributes']['name'] ?? null;
            return $name === 'Alpha Department' || $name === 'Zulu Department';
        })->values();

        $this->assertCount(2, $testDepts);
        $this->assertEquals('Alpha Department', $testDepts[0]['attributes']['name']);
        $this->assertEquals('Zulu Department', $testDepts[1]['attributes']['name']);
    }

    public function test_admin_can_filter_departments_by_active_status(): void
    {
        $admin = $this->getAdminUser();

        Department::factory()->count(2)->active()->create();
        Department::factory()->count(1)->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments?filter[isActive]=1');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_departments_with_permission(): void
    {
        $tech = $this->getTechUser();

        Department::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_departments(): void
    {
        $customer = $this->getCustomerUser();

        Department::factory()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_departments(): void
    {
        $response = $this->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments');

        $response->assertStatus(401);
    }

    public function test_can_paginate_departments(): void
    {
        $admin = $this->getAdminUser();

        Department::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    public function test_can_include_manager_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $manager = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $department->update(['manager_id' => $manager->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments?include=manager');

        $response->assertOk();
    }

    public function test_can_search_departments_by_name(): void
    {
        $admin = $this->getAdminUser();

        Department::factory()->create(['name' => 'Sales Department']);
        Department::factory()->create(['name' => 'HR Department']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments?filter[name]=Sales Department');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
    }
}

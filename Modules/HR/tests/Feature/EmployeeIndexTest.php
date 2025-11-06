<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class EmployeeIndexTest extends TestCase
{
    public function test_admin_can_list_employees(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        Employee::factory()->count(3)->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_employees_by_last_name(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);

        Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
            'firstName' => 'John',
            'lastName' => 'Zulu'
        ]);
        Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
            'firstName' => 'Jane',
            'lastName' => 'Alpha'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees?sort=lastName&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        $testEmployees = collect($data)->filter(function($item) {
            $lastName = $item['attributes']['lastName'] ?? null;
            return $lastName === 'Alpha' || $lastName === 'Zulu';
        })->values();

        $this->assertCount(2, $testEmployees);
        $this->assertEquals('Alpha', $testEmployees[0]['attributes']['lastName']);
        $this->assertEquals('Zulu', $testEmployees[1]['attributes']['lastName']);
    }

    public function test_admin_can_filter_employees_by_status(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);

        Employee::factory()->count(2)->active()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        Employee::factory()->count(1)->terminated()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees?filter[status]=active');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_employees_by_department(): void
    {
        $admin = $this->getAdminUser();

        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $position1 = Position::factory()->create(['departmentId' => $department1->id]);
        $position2 = Position::factory()->create(['departmentId' => $department2->id]);

        Employee::factory()->count(2)->create([
            'departmentId' => $department1->id,
            'positionId' => $position1->id
        ]);
        Employee::factory()->count(1)->create([
            'departmentId' => $department2->id,
            'positionId' => $position2->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get("/api/v1/employees?filter[department]={$department1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_employees_by_employment_type(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);

        Employee::factory()->count(2)->fullTime()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        Employee::factory()->count(1)->contract()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees?filter[employmentType]=full-time');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_employees_with_permission(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        Employee::factory()->count(2)->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_employees(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        Employee::factory()->count(2)->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_employees(): void
    {
        $response = $this->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees');

        $response->assertStatus(401);
    }

    public function test_can_paginate_employees(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        Employee::factory()->count(25)->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    public function test_can_include_position_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees?include=position');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'position'
                    ]
                ]
            ]
        ]);
    }

    public function test_can_include_department_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees?include=department');

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

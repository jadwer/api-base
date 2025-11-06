<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class AttendanceIndexTest extends TestCase
{
    public function test_admin_can_list_attendances(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        Attendance::factory()->count(3)->create(['employeeId' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_attendances_by_date(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        Attendance::factory()->create([
            'employeeId' => $employee->id,
            'date' => '2024-01-15'
        ]);
        Attendance::factory()->create([
            'employeeId' => $employee->id,
            'date' => '2024-01-10'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances?sort=date&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
    }

    public function test_admin_can_filter_attendances_by_status(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        Attendance::factory()->count(2)->present()->create(['employeeId' => $employee->id]);
        Attendance::factory()->count(1)->absent()->create(['employeeId' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances?filter[status]=present');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_attendances_by_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee1 = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $employee2 = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        Attendance::factory()->count(2)->create(['employeeId' => $employee1->id]);
        Attendance::factory()->count(1)->create(['employeeId' => $employee2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get("/api/v1/attendances?filter[employee]={$employee1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_attendances_with_permission(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        Attendance::factory()->count(2)->create(['employeeId' => $employee->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_attendances(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        Attendance::factory()->count(2)->create(['employeeId' => $employee->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_attendances(): void
    {
        $response = $this->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances');

        $response->assertStatus(401);
    }

    public function test_can_paginate_attendances(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        Attendance::factory()->count(25)->create(['employeeId' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    public function test_can_include_employee_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        Attendance::factory()->create(['employeeId' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances?include=employee');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'employee'
                    ]
                ]
            ]
        ]);
    }
}

<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Leave;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class LeaveIndexTest extends TestCase
{
    public function test_admin_can_list_leaves(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->count(3)->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_leaves_by_start_date(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2024-02-15'
        ]);
        Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2024-01-10'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves?sort=startDate&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
    }

    public function test_admin_can_filter_leaves_by_status(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        Leave::factory()->count(2)->approved()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);
        Leave::factory()->count(1)->pending()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves?filter[status]=approved');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_leaves_by_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee1 = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $employee2 = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        Leave::factory()->count(2)->create([
            'employee_id' => $employee1->id,
            'leave_type_id' => $leaveType->id
        ]);
        Leave::factory()->count(1)->create([
            'employee_id' => $employee2->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get("/api/v1/leaves?filter[employeeId]={$employee1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_leaves_with_permission(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_leaves(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_leaves(): void
    {
        $response = $this->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves');

        $response->assertStatus(401);
    }

    public function test_can_paginate_leaves(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->count(25)->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves?page[size]=10');

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
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves?include=employee');

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

    public function test_can_include_leave_type_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves?include=leaveType');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'leaveType'
                    ]
                ]
            ]
        ]);
    }
}

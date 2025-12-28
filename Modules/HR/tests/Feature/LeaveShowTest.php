<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Leave;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class LeaveShowTest extends TestCase
{
    public function test_admin_can_view_leave(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2024-01-15',
            'end_date' => '2024-01-20',
            'days_requested' => 5,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get("/api/v1/leaves/{$leave->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'startDate',
                    'endDate',
                    'daysRequested',
                    'status'
                ]
            ]
        ]);
        $this->assertEquals($leave->id, $response->json('data.id'));
    }

    public function test_tech_user_can_view_leave(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get("/api/v1/leaves/{$leave->id}");

        $response->assertOk();
        $this->assertEquals($leave->id, $response->json('data.id'));
    }

    public function test_customer_user_cannot_view_leave(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get("/api/v1/leaves/{$leave->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_leave(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->jsonApi()
            ->expects('leaves')
            ->get("/api/v1/leaves/{$leave->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_leave(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get('/api/v1/leaves/99999');

        $response->assertStatus(404);
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
        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get("/api/v1/leaves/{$leave->id}?include=employee");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'employee'
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
        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->get("/api/v1/leaves/{$leave->id}?include=leaveType");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'leaveType'
                ]
            ]
        ]);
    }
}

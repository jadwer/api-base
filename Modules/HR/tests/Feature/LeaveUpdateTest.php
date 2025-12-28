<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Leave;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class LeaveUpdateTest extends TestCase
{
    public function test_admin_can_update_leave(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->pending()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $data = [
            'type' => 'leaves',
            'id' => (string) $leave->id,
            'attributes' => [
                'status' => 'approved',
                'notes' => 'Approved by admin'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch("/api/v1/leaves/{$leave->id}");

        $response->assertOk();
        $this->assertEquals('approved', $response->json('data.attributes.status'));
        $this->assertEquals('Approved by admin', $response->json('data.attributes.notes'));

        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'approved',
            'notes' => 'Approved by admin'
        ]);
    }

    public function test_admin_can_reject_leave(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->pending()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $data = [
            'type' => 'leaves',
            'id' => (string) $leave->id,
            'attributes' => [
                'status' => 'rejected',
                'notes' => 'Staff shortage during this period'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch("/api/v1/leaves/{$leave->id}");

        $response->assertOk();
        $this->assertEquals('rejected', $response->json('data.attributes.status'));
    }

    public function test_tech_user_can_update_leave(): void
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

        $data = [
            'type' => 'leaves',
            'id' => (string) $leave->id,
            'attributes' => [
                'reason' => 'Updated reason'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch("/api/v1/leaves/{$leave->id}");

        $response->assertOk();
        $this->assertEquals('Updated reason', $response->json('data.attributes.reason'));
    }

    public function test_customer_user_cannot_update_leave(): void
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

        $data = [
            'type' => 'leaves',
            'id' => (string) $leave->id,
            'attributes' => [
                'status' => 'approved'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch("/api/v1/leaves/{$leave->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_leave(): void
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

        $data = [
            'type' => 'leaves',
            'id' => (string) $leave->id,
            'attributes' => [
                'status' => 'approved'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch("/api/v1/leaves/{$leave->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_leave(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leaves',
            'id' => '99999',
            'attributes' => [
                'status' => 'approved'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch('/api/v1/leaves/99999');

        $response->assertStatus(404);
    }

    public function test_validation_rules_apply_on_update(): void
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

        $data = [
            'type' => 'leaves',
            'id' => (string) $leave->id,
            'attributes' => [
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch("/api/v1/leaves/{$leave->id}");

        $response->assertStatus(422);
    }

    public function test_can_cancel_leave(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->approved()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $data = [
            'type' => 'leaves',
            'id' => (string) $leave->id,
            'attributes' => [
                'status' => 'cancelled'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch("/api/v1/leaves/{$leave->id}");

        $response->assertOk();
        $this->assertEquals('cancelled', $response->json('data.attributes.status'));
    }

    public function test_can_update_only_specific_fields(): void
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
            'status' => 'pending',
            'days_requested' => 5
        ]);

        $data = [
            'type' => 'leaves',
            'id' => (string) $leave->id,
            'attributes' => [
                'reason' => 'Updated reason only'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->patch("/api/v1/leaves/{$leave->id}");

        $response->assertOk();
        $this->assertEquals('Updated reason only', $response->json('data.attributes.reason'));
        $this->assertEquals('pending', $response->json('data.attributes.status'));
        $this->assertEquals(5, $response->json('data.attributes.daysRequested'));
    }
}

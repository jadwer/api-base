<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Leave;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class LeaveDestroyTest extends TestCase
{
    public function test_admin_can_delete_leave(): void
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
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('leaves', [
            'id' => $leave->id
        ]);
    }

    public function test_admin_can_delete_pending_leave(): void
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

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leaves', ['id' => $leave->id]);
    }

    public function test_admin_can_delete_approved_leave(): void
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

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leaves', ['id' => $leave->id]);
    }

    public function test_admin_can_delete_rejected_leave(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->rejected()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leaves', ['id' => $leave->id]);
    }

    public function test_tech_user_can_delete_leave(): void
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
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leaves', ['id' => $leave->id]);
    }

    public function test_customer_user_cannot_delete_leave(): void
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
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('leaves', ['id' => $leave->id]);
    }

    public function test_guest_cannot_delete_leave(): void
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
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('leaves', ['id' => $leave->id]);
    }

    public function test_returns_404_for_nonexistent_leave(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->delete('/api/v1/leaves/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_leave_does_not_affect_employee(): void
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
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_deleting_leave_does_not_affect_leave_type(): void
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
            ->delete("/api/v1/leaves/{$leave->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id]);
    }

    public function test_deleting_leave_does_not_affect_other_leaves(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $leave1 = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);
        $leave2 = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->delete("/api/v1/leaves/{$leave1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('leaves', ['id' => $leave1->id]);
        $this->assertDatabaseHas('leaves', ['id' => $leave2->id]);
    }
}

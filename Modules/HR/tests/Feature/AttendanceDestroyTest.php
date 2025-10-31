<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class AttendanceDestroyTest extends TestCase
{
    public function test_admin_can_delete_attendance(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->delete("/api/v1/attendances/{$attendance->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id
        ]);
    }

    public function test_admin_can_delete_present_attendance(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->present()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->delete("/api/v1/attendances/{$attendance->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_admin_can_delete_absent_attendance(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->absent()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->delete("/api/v1/attendances/{$attendance->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_tech_user_can_delete_attendance(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->delete("/api/v1/attendances/{$attendance->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_customer_user_cannot_delete_attendance(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->delete("/api/v1/attendances/{$attendance->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    public function test_guest_cannot_delete_attendance(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $response = $this->jsonApi()
            ->expects('attendances')
            ->delete("/api/v1/attendances/{$attendance->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    public function test_returns_404_for_nonexistent_attendance(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->delete('/api/v1/attendances/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_attendance_does_not_affect_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->delete("/api/v1/attendances/{$attendance->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_deleting_attendance_does_not_affect_other_attendances(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $attendance1 = Attendance::factory()->create(['employee_id' => $employee->id]);
        $attendance2 = Attendance::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->delete("/api/v1/attendances/{$attendance1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('attendances', ['id' => $attendance1->id]);
        $this->assertDatabaseHas('attendances', ['id' => $attendance2->id]);
    }
}

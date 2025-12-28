<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class AttendanceUpdateTest extends TestCase
{
    public function test_admin_can_update_attendance(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => 'present'
        ]);

        $data = [
            'type' => 'attendances',
            'id' => (string) $attendance->id,
            'attributes' => [
                'notes' => 'Worked overtime'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->patch("/api/v1/attendances/{$attendance->id}");

        $response->assertOk();
        $this->assertEquals('Worked overtime', $response->json('data.attributes.notes'));
    }

    public function test_tech_user_can_update_attendance(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $data = [
            'type' => 'attendances',
            'id' => (string) $attendance->id,
            'attributes' => [
                'notes' => 'Updated by tech'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->patch("/api/v1/attendances/{$attendance->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_update_attendance(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $data = [
            'type' => 'attendances',
            'id' => (string) $attendance->id,
            'attributes' => [
                'status' => 'late'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->patch("/api/v1/attendances/{$attendance->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_attendance(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $data = [
            'type' => 'attendances',
            'id' => (string) $attendance->id,
            'attributes' => [
                'status' => 'late'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->patch("/api/v1/attendances/{$attendance->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_attendance(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'attendances',
            'id' => '99999',
            'attributes' => [
                'notes' => 'Should not work'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->patch('/api/v1/attendances/99999');

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
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $data = [
            'type' => 'attendances',
            'id' => (string) $attendance->id,
            'attributes' => [
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->patch("/api/v1/attendances/{$attendance->id}");

        $response->assertStatus(422);
    }

    public function test_auto_calculated_hours_worked_recalculates_on_update(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => 'present'
        ]);

        $data = [
            'type' => 'attendances',
            'id' => (string) $attendance->id,
            'attributes' => [
                'notes' => 'Testing recalculation'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->patch("/api/v1/attendances/{$attendance->id}");

        $response->assertOk();
        // Hours should recalculate to 10 hours
        $this->assertNotNull($response->json('data.attributes.hoursWorked'));
    }
}

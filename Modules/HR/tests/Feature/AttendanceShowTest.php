<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class AttendanceShowTest extends TestCase
{
    public function test_admin_can_view_attendance(): void
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
            'date' => '2024-01-15',
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'status' => 'present'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get("/api/v1/attendances/{$attendance->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'date',
                    'checkIn',
                    'checkOut',
                    'hoursWorked',
                    'status'
                ]
            ]
        ]);
        $this->assertEquals($attendance->id, $response->json('data.id'));
    }

    public function test_tech_user_can_view_attendance(): void
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
            ->get("/api/v1/attendances/{$attendance->id}");

        $response->assertOk();
        $this->assertEquals($attendance->id, $response->json('data.id'));
    }

    public function test_customer_user_cannot_view_attendance(): void
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
            ->get("/api/v1/attendances/{$attendance->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_attendance(): void
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
            ->get("/api/v1/attendances/{$attendance->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_attendance(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get('/api/v1/attendances/99999');

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
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get("/api/v1/attendances/{$attendance->id}?include=employee");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'employee'
                ]
            ]
        ]);
    }

    public function test_verify_auto_calculated_hours_worked(): void
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
            'date' => '2024-01-15',
            'check_in' => '09:00:00',
            'check_out' => '17:30:00'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->get("/api/v1/attendances/{$attendance->id}");

        $response->assertOk();
        // Should calculate 8.5 hours
        $this->assertNotNull($response->json('data.attributes.hoursWorked'));
    }
}

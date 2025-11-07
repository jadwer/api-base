<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class AttendanceStoreTest extends TestCase
{
    public function test_admin_can_create_attendance(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'attendanceDate' => '2024-01-15',
                'checkInTime' => '09:00:00',
                'checkOutTime' => '17:30:00',
                'overtimeHours' => 0,
                'status' => 'present',
                'notes' => 'Regular day'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
        $this->assertEquals('present', $response->json('data.attributes.status'));

        $this->assertDatabaseHas('attendances', [
            'employeeId' => $employee->id,
            'attendanceDate' => '2024-01-15',
            'status' => 'present'
        ]);
    }

    public function test_tech_user_can_create_attendance(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'attendanceDate' => '2024-01-16',
                'checkInTime' => '08:30:00',
                'checkOutTime' => '17:00:00',
                'status' => 'present'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_attendance(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'attendanceDate' => '2024-01-15',
                'checkInTime' => '09:00:00',
                'status' => 'present'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_attendance(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'attendanceDate' => '2024-01-15',
                'checkInTime' => '09:00:00',
                'status' => 'present'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(401);
    }

    public function test_employee_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'attendanceDate' => '2024-01-15',
                'checkInTime' => '09:00:00',
                'status' => 'present'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(422);
    }

    public function test_attendance_date_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'checkInTime' => '09:00:00',
                'status' => 'present'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(422);
    }

    public function test_check_in_time_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'attendanceDate' => '2024-01-15',
                'status' => 'present'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(422);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'attendanceDate' => '2024-01-15',
                'checkInTime' => '09:00:00',
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(422);
    }

    public function test_auto_calculated_hours_worked_works_correctly(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'attendanceDate' => '2024-01-15',
                'checkInTime' => '09:00:00',
                'checkOutTime' => '17:00:00',
                'status' => 'present'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
        // Should calculate 8 hours
        $this->assertNotNull($response->json('data.attributes.hoursWorked'));
    }

    public function test_check_out_time_is_optional(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'employeeId' => $employee->id,
                'attendanceDate' => '2024-01-15',
                'checkInTime' => '09:00:00',
                'status' => 'present'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
    }
}

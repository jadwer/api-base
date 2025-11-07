<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Leave;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class LeaveStoreTest extends TestCase
{
    public function test_admin_can_create_leave(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'startDate' => '2024-02-15',
                'endDate' => '2024-02-20',
                'daysRequested' => 5,
                'status' => 'pending',
                'reason' => 'Annual vacation'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertCreated();
        $this->assertEquals('pending', $response->json('data.attributes.status'));
        $this->assertEquals(5, $response->json('data.attributes.daysRequested'));

        $this->assertDatabaseHas('leaves', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending'
        ]);
    }

    public function test_tech_user_can_create_leave(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'startDate' => '2024-03-01',
                'endDate' => '2024-03-05',
                'daysRequested' => 4,
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_leave(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'startDate' => '2024-02-15',
                'endDate' => '2024-02-20',
                'daysRequested' => 5,
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_leave(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'startDate' => '2024-02-15',
                'endDate' => '2024-02-20',
                'daysRequested' => 5,
                'status' => 'pending'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertStatus(401);
    }

    public function test_employee_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'leave_type_id' => $leaveType->id,
                'startDate' => '2024-02-15',
                'endDate' => '2024-02-20',
                'daysRequested' => 5,
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertStatus(422);
    }

    public function test_leave_type_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'startDate' => '2024-02-15',
                'endDate' => '2024-02-20',
                'daysRequested' => 5,
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertStatus(422);
    }

    public function test_start_date_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'endDate' => '2024-02-20',
                'daysRequested' => 5,
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertStatus(422);
    }

    public function test_end_date_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'startDate' => '2024-02-15',
                'daysRequested' => 5,
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertStatus(422);
    }

    public function test_days_requested_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'startDate' => '2024-02-15',
                'endDate' => '2024-02-20',
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertStatus(422);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $leaveType = LeaveType::factory()->create();

        $data = [
            'type' => 'leaves',
            'attributes' => [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'startDate' => '2024-02-15',
                'endDate' => '2024-02-20',
                'daysRequested' => 5,
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leaves')
            ->withData($data)
            ->post('/api/v1/leaves');

        $response->assertStatus(422);
    }
}

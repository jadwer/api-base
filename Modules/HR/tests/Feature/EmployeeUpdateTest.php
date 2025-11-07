<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class EmployeeUpdateTest extends TestCase
{
    public function test_admin_can_update_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'firstName' => 'Old',
            'lastName' => 'Name',
            'salary' => 40000.00
        ]);

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'firstName' => 'Updated',
                'lastName' => 'Employee',
                'salary' => 55000.00,
                'status' => 'inactive'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $this->assertEquals('Updated', $response->json('data.attributes.firstName'));
        $this->assertEquals('Employee', $response->json('data.attributes.lastName'));
        $this->assertEquals(55000.00, $response->json('data.attributes.salary'));
        $this->assertEquals('inactive', $response->json('data.attributes.status'));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'firstName' => 'Updated',
            'salary' => 55000.00
        ]);
    }

    public function test_admin_can_terminate_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->active()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'status' => 'terminated'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $this->assertEquals('terminated', $response->json('data.attributes.status'));
    }

    public function test_tech_user_can_update_employee(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'phone' => '+9876543210'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $this->assertEquals('+9876543210', $response->json('data.attributes.phone'));
    }

    public function test_customer_user_cannot_update_employee(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'salary' => 100000.00
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_employee(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'salary' => 100000.00
            ]
        ];

        $response = $this->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_employee(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'employees',
            'id' => '99999',
            'attributes' => [
                'salary' => 50000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch('/api/v1/employees/99999');

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

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertStatus(422);
    }

    public function test_can_update_only_specific_fields(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'firstName' => 'Original',
            'lastName' => 'Name',
            'salary' => 40000.00,
            'status' => 'active'
        ]);

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'phone' => '+1234567890'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $this->assertEquals('Original', $response->json('data.attributes.firstName'));
        $this->assertEquals('Name', $response->json('data.attributes.lastName'));
        $this->assertEquals(40000.00, $response->json('data.attributes.salary'));
        $this->assertEquals('+1234567890', $response->json('data.attributes.phone'));
    }

    public function test_can_change_employee_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position1 = Position::factory()->create(['department_id' => $department->id]);
        $position2 = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position1->id
        ]);

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'position_id' => $position2->id
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $this->assertEquals($position2->id, $response->json('data.attributes.positionId'));
    }

    public function test_can_change_employee_department(): void
    {
        $admin = $this->getAdminUser();

        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $position1 = Position::factory()->create(['department_id' => $department1->id]);
        $position2 = Position::factory()->create(['department_id' => $department2->id]);

        $employee = Employee::factory()->create([
            'department_id' => $department1->id,
            'position_id' => $position1->id
        ]);

        $data = [
            'type' => 'employees',
            'id' => (string) $employee->id,
            'attributes' => [
                'department_id' => $department2->id,
                'position_id' => $position2->id
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->patch("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $this->assertEquals($department2->id, $response->json('data.attributes.departmentId'));
    }
}

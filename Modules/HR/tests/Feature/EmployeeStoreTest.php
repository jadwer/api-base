<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class EmployeeStoreTest extends TestCase
{
    public function test_admin_can_create_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1234567890',
                'hire_date' => '2024-01-15',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 55000.00,
                'status' => 'active',
                'employment_type' => 'full-time'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertCreated();
        $this->assertEquals('John', $response->json('data.attributes.firstName'));
        $this->assertEquals('Smith', $response->json('data.attributes.lastName'));
        $this->assertEquals(55000.00, $response->json('data.attributes.salary'));

        $this->assertDatabaseHas('employees', [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@example.com',
            'status' => 'active'
        ]);
    }

    public function test_tech_user_can_create_employee(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@example.com',
                'hire_date' => '2024-02-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 48000.00,
                'status' => 'active',
                'employment_type' => 'full-time'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertCreated();
        $this->assertEquals('Jane', $response->json('data.attributes.firstName'));
    }

    public function test_customer_user_cannot_create_employee(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'Should',
                'last_name' => 'NotCreate',
                'email' => 'test@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 40000.00
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_employee(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'Guest',
                'last_name' => 'User',
                'email' => 'guest@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 40000.00
            ]
        ];

        $response = $this->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(401);
    }

    public function test_first_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'last_name' => 'Smith',
                'email' => 'test@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 40000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_last_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'John',
                'email' => 'test@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 40000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_email_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 40000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_email_must_be_unique(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'email' => 'existing@example.com'
        ]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'existing@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 40000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_salary_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'test@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_salary_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'test@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 'not_a_number'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'test@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 40000.00,
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_employment_type_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'test@example.com',
                'hire_date' => '2024-01-01',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'salary' => 40000.00,
                'employment_type' => 'invalid_type'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }
}

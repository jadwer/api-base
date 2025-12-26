<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class EmployeeShowTest extends TestCase
{
    public function test_admin_can_view_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'salary' => 50000.00,
            'status' => 'active'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'firstName',
                    'lastName',
                    'email',
                    'salary',
                    'status'
                ]
            ]
        ]);
        $this->assertEquals($employee->id, $response->json('data.id'));
        $this->assertEquals('John', $response->json('data.attributes.firstName'));
    }

    public function test_tech_user_can_view_employee(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $this->assertEquals($employee->id, $response->json('data.id'));
    }

    public function test_customer_user_cannot_view_employee(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get("/api/v1/employees/{$employee->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_employee(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $response = $this->jsonApi()
            ->expects('employees')
            ->get("/api/v1/employees/{$employee->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_employee(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees/99999');

        $response->assertStatus(404);
    }

    public function test_can_include_position_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get("/api/v1/employees/{$employee->id}?include=position");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'position'
                ]
            ]
        ]);
    }

    public function test_can_include_department_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get("/api/v1/employees/{$employee->id}?include=department");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'department'
                ]
            ]
        ]);
    }

    public function test_terminated_employee_shows_correct_status(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->terminated()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get("/api/v1/employees/{$employee->id}");

        $response->assertOk();
        $this->assertEquals('terminated', $response->json('data.attributes.status'));
    }
}

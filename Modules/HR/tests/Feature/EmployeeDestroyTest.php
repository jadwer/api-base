<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class EmployeeDestroyTest extends TestCase
{
    public function test_admin_can_delete_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id
        ]);
    }

    public function test_admin_can_delete_active_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->active()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_admin_can_delete_terminated_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->terminated()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_tech_user_can_delete_employee(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_customer_user_cannot_delete_employee(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_guest_cannot_delete_employee(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_returns_404_for_nonexistent_employee(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete('/api/v1/employees/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_employee_does_not_affect_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_deleting_employee_does_not_affect_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }

    public function test_deleting_employee_does_not_affect_other_employees(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);

        $employee1 = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $employee2 = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->delete("/api/v1/employees/{$employee1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('employees', ['id' => $employee1->id]);
        $this->assertDatabaseHas('employees', ['id' => $employee2->id]);
    }
}

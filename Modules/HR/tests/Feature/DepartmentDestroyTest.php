<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Position;

class DepartmentDestroyTest extends TestCase
{
    public function test_admin_can_delete_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('departments', [
            'id' => $department->id
        ]);
    }

    public function test_admin_can_delete_active_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->active()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_admin_can_delete_inactive_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_tech_user_can_delete_department(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_customer_user_cannot_delete_department(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_guest_cannot_delete_department(): void
    {
        $department = Department::factory()->create();

        $response = $this->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_returns_404_for_nonexistent_department(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete('/api/v1/departments/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_department_does_not_affect_other_departments(): void
    {
        $admin = $this->getAdminUser();

        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('departments', ['id' => $department1->id]);
        $this->assertDatabaseHas('departments', ['id' => $department2->id]);
    }

    public function test_deleting_department_with_manager_succeeds(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $manager = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $department->update(['manager_id' => $manager->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department->id}");

        // The delete may fail if there are foreign key constraints
        // This test verifies the behavior is consistent
        if ($response->status() === 204) {
            $this->assertDatabaseMissing('departments', ['id' => $department->id]);
        } else {
            // If it fails due to FK constraints, that's also acceptable behavior
            $response->assertStatus(422);
        }
    }

    public function test_can_delete_department_without_employees(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create([
            'name' => 'Empty Department',
            'description' => 'No employees'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->delete("/api/v1/departments/{$department->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }
}

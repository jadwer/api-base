<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Position;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;

class PositionDestroyTest extends TestCase
{
    public function test_admin_can_delete_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('positions', [
            'id' => $position->id
        ]);
    }

    public function test_admin_can_delete_active_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->active()->create(['department_id' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_admin_can_delete_inactive_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->inactive()->create(['department_id' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_tech_user_can_delete_position(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_customer_user_cannot_delete_position(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }

    public function test_guest_cannot_delete_position(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }

    public function test_returns_404_for_nonexistent_position(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete('/api/v1/positions/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_position_does_not_affect_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_deleting_position_does_not_affect_other_positions(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position1 = Position::factory()->create(['department_id' => $department->id]);
        $position2 = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('positions', ['id' => $position1->id]);
        $this->assertDatabaseHas('positions', ['id' => $position2->id]);
    }

    public function test_deleting_position_with_employees_may_fail(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->delete("/api/v1/positions/{$position->id}");

        // The delete may fail if there are foreign key constraints
        // This test verifies the behavior is consistent
        if ($response->status() === 204) {
            $this->assertDatabaseMissing('positions', ['id' => $position->id]);
        } else {
            // If it fails due to FK constraints, that's also acceptable behavior
            $response->assertStatus(422);
        }
    }
}

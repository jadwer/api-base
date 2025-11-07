<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Position;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;

class PositionShowTest extends TestCase
{
    public function test_admin_can_view_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create([
            'department_id' => $department->id,
            'title' => 'Senior Software Engineer',
            'description' => 'Full-stack development',
            'min_salary' => 50000.00,
            'max_salary' => 80000.00,
            'is_active' => true
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get("/api/v1/positions/{$position->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'title',
                    'description',
                    'minSalary',
                    'maxSalary',
                    'is_active'
                ]
            ]
        ]);
        $this->assertEquals($position->id, $response->json('data.id'));
        $this->assertEquals('Senior Software Engineer', $response->json('data.attributes.title'));
    }

    public function test_tech_user_can_view_position(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get("/api/v1/positions/{$position->id}");

        $response->assertOk();
        $this->assertEquals($position->id, $response->json('data.id'));
    }

    public function test_customer_user_cannot_view_position(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get("/api/v1/positions/{$position->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_position(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->jsonApi()
            ->expects('positions')
            ->get("/api/v1/positions/{$position->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_position(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get('/api/v1/positions/99999');

        $response->assertStatus(404);
    }

    public function test_can_include_department_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get("/api/v1/positions/{$position->id}?include=department");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'department'
                ]
            ]
        ]);
    }

    public function test_can_include_employees_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        Employee::factory()->count(2)->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get("/api/v1/positions/{$position->id}?include=employees");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'employees'
                ]
            ]
        ]);
    }

    public function test_inactive_position_shows_correct_status(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->inactive()->create(['department_id' => $department->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->get("/api/v1/positions/{$position->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }
}

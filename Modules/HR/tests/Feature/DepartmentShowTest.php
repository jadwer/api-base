<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Position;

class DepartmentShowTest extends TestCase
{
    public function test_admin_can_view_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create([
            'name' => 'Engineering Department',
            'description' => 'Software development team',
            'isActive' => true
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'description',
                    'isActive'
                ]
            ]
        ]);
        $this->assertEquals($department->id, $response->json('data.id'));
        $this->assertEquals('Engineering Department', $response->json('data.attributes.name'));
    }

    public function test_tech_user_can_view_department(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $this->assertEquals($department->id, $response->json('data.id'));
    }

    public function test_customer_user_cannot_view_department(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get("/api/v1/departments/{$department->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_department(): void
    {
        $department = Department::factory()->create();

        $response = $this->jsonApi()
            ->expects('departments')
            ->get("/api/v1/departments/{$department->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_department(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get('/api/v1/departments/99999');

        $response->assertStatus(404);
    }

    public function test_can_include_manager_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $manager = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $department->update(['manager_id' => $manager->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get("/api/v1/departments/{$department->id}?include=manager");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'manager'
                ]
            ]
        ]);
    }

    public function test_can_include_employees_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        Employee::factory()->count(3)->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get("/api/v1/departments/{$department->id}?include=employees");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'employees'
                ]
            ]
        ]);
    }

    public function test_inactive_department_shows_correct_status(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->get("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }
}

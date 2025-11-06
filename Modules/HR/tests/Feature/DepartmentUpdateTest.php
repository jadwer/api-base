<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Position;

class DepartmentUpdateTest extends TestCase
{
    public function test_admin_can_update_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create([
            'name' => 'Old Department Name',
            'description' => 'Old description'
        ]);

        $data = [
            'type' => 'departments',
            'id' => (string) $department->id,
            'attributes' => [
                'name' => 'New Department Name',
                'description' => 'Updated description',
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $this->assertEquals('New Department Name', $response->json('data.attributes.name'));
        $this->assertEquals('Updated description', $response->json('data.attributes.description'));
        $this->assertFalse($response->json('data.attributes.isActive'));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'New Department Name',
            'isActive' => false
        ]);
    }

    public function test_admin_can_update_department_manager(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $manager = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);

        $data = [
            'type' => 'departments',
            'id' => (string) $department->id,
            'attributes' => [
                'manager_id' => $manager->id
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $this->assertEquals($manager->id, $response->json('data.attributes.managerId'));
    }

    public function test_admin_can_deactivate_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->active()->create();

        $data = [
            'type' => 'departments',
            'id' => (string) $department->id,
            'attributes' => [
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }

    public function test_tech_user_can_update_department(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'departments',
            'id' => (string) $department->id,
            'attributes' => [
                'description' => 'Tech user updated description'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $this->assertEquals('Tech user updated description', $response->json('data.attributes.description'));
    }

    public function test_customer_user_cannot_update_department(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'departments',
            'id' => (string) $department->id,
            'attributes' => [
                'description' => 'Should not update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_department(): void
    {
        $department = Department::factory()->create();

        $data = [
            'type' => 'departments',
            'id' => (string) $department->id,
            'attributes' => [
                'description' => 'Should not update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_department(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'departments',
            'id' => '99999',
            'attributes' => [
                'description' => 'Should not work'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch('/api/v1/departments/99999');

        $response->assertStatus(404);
    }

    public function test_validation_rules_apply_on_update(): void
    {
        $admin = $this->getAdminUser();

        $department1 = Department::factory()->create(['name' => 'Department One']);
        $department2 = Department::factory()->create(['name' => 'Department Two']);

        $data = [
            'type' => 'departments',
            'id' => (string) $department2->id,
            'attributes' => [
                'name' => 'Department One' // Duplicate name
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department2->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_can_update_only_specific_fields(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'isActive' => true
        ]);

        $data = [
            'type' => 'departments',
            'id' => (string) $department->id,
            'attributes' => [
                'description' => 'Only updating description'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $this->assertEquals('Original Name', $response->json('data.attributes.name'));
        $this->assertEquals('Only updating description', $response->json('data.attributes.description'));
        $this->assertTrue($response->json('data.attributes.isActive'));
    }

    public function test_can_remove_manager_from_department(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $manager = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $department->update(['manager_id' => $manager->id]);

        $data = [
            'type' => 'departments',
            'id' => (string) $department->id,
            'attributes' => [
                'manager_id' => null
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->patch("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $this->assertNull($response->json('data.attributes.managerId'));
    }
}

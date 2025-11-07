<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Position;

class DepartmentStoreTest extends TestCase
{
    public function test_admin_can_create_department(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'Engineering Department',
                'description' => 'Software development and IT infrastructure',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'description',
                    'is_active'
                ]
            ]
        ]);

        $this->assertEquals('Engineering Department', $response->json('data.attributes.name'));
        $this->assertEquals('Software development and IT infrastructure', $response->json('data.attributes.description'));
        $this->assertTrue($response->json('data.attributes.isActive'));

        $this->assertDatabaseHas('departments', [
            'name' => 'Engineering Department',
            'is_active' => true
        ]);
    }

    public function test_admin_can_create_department_with_manager(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $manager = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'Sales Department',
                'description' => 'Sales and business development',
                'manager_id' => $manager->id,
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertCreated();
        $this->assertEquals('Sales Department', $response->json('data.attributes.name'));
        $this->assertEquals($manager->id, $response->json('data.attributes.managerId'));
    }

    public function test_tech_user_can_create_department(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'HR Department',
                'description' => 'Human resources management',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertCreated();
        $this->assertEquals('HR Department', $response->json('data.attributes.name'));
    }

    public function test_customer_user_cannot_create_department(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'Should Not Create',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_department(): void
    {
        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'Should Not Create',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertStatus(401);
    }

    public function test_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'departments',
            'attributes' => [
                'description' => 'Missing name field',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_name_must_be_unique(): void
    {
        $admin = $this->getAdminUser();

        $existingDepartment = Department::factory()->create(['name' => 'Marketing Department']);

        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'Marketing Department',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'Finance Department',
                'description' => 'Financial operations'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertCreated();
        $this->assertTrue($response->json('data.attributes.isActive'));
    }

    public function test_can_create_inactive_department(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'Archived Department',
                'description' => 'Old department',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertCreated();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }

    public function test_description_is_optional(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'departments',
            'attributes' => [
                'name' => 'Operations Department',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('departments')
            ->withData($data)
            ->post('/api/v1/departments');

        $response->assertCreated();
        $this->assertEquals('Operations Department', $response->json('data.attributes.name'));
    }
}

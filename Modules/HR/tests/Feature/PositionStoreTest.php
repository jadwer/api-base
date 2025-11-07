<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Position;
use Modules\HR\Models\Department;

class PositionStoreTest extends TestCase
{
    public function test_admin_can_create_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Software Engineer',
                'departmentId' => $department->id,
                'description' => 'Develop and maintain software applications',
                'min_salary' => 40000.00,
                'max_salary' => 70000.00,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'title',
                    'description',
                    'minSalary',
                    'maxSalary',
                    'isActive'
                ]
            ]
        ]);

        $this->assertEquals('Software Engineer', $response->json('data.attributes.title'));
        $this->assertEquals(40000.00, $response->json('data.attributes.minSalary'));
        $this->assertEquals(70000.00, $response->json('data.attributes.maxSalary'));

        $this->assertDatabaseHas('positions', [
            'title' => 'Software Engineer',
            'departmentId' => $department->id,
            'isActive' => true
        ]);
    }

    public function test_admin_can_create_position_without_salary_range(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Junior Developer',
                'departmentId' => $department->id,
                'description' => 'Entry level position',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertCreated();
        $this->assertEquals('Junior Developer', $response->json('data.attributes.title'));
    }

    public function test_tech_user_can_create_position(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Data Analyst',
                'departmentId' => $department->id,
                'description' => 'Analyze business data',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertCreated();
        $this->assertEquals('Data Analyst', $response->json('data.attributes.title'));
    }

    public function test_customer_user_cannot_create_position(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Should Not Create',
                'departmentId' => $department->id,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_position(): void
    {
        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Should Not Create',
                'departmentId' => $department->id,
                'isActive' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertStatus(401);
    }

    public function test_title_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'departmentId' => $department->id,
                'description' => 'Missing title',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_department_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'No Department Position',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_min_salary_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Invalid Salary',
                'departmentId' => $department->id,
                'min_salary' => 'not_a_number',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_max_salary_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Invalid Salary',
                'departmentId' => $department->id,
                'max_salary' => 'not_a_number',
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Default Active Position',
                'departmentId' => $department->id,
                'description' => 'Test default active'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertCreated();
        $this->assertTrue($response->json('data.attributes.isActive'));
    }

    public function test_can_create_inactive_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Inactive Position',
                'departmentId' => $department->id,
                'description' => 'This position is not active',
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertCreated();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }

    public function test_description_is_optional(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Position Without Description',
                'departmentId' => $department->id,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->post('/api/v1/positions');

        $response->assertCreated();
        $this->assertEquals('Position Without Description', $response->json('data.attributes.title'));
    }
}

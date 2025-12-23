<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Position;
use Modules\HR\Models\Department;

class PositionUpdateTest extends TestCase
{
    public function test_admin_can_update_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create([
            'department_id' => $department->id,
            'title' => 'Old Title',
            'description' => 'Old description'
        ]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'attributes' => [
                'title' => 'New Title',
                'description' => 'Updated description',
                'minSalary' => 45000.00,
                'maxSalary' => 75000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertOk();
        $this->assertEquals('New Title', $response->json('data.attributes.title'));
        $this->assertEquals('Updated description', $response->json('data.attributes.description'));
        $this->assertEquals(45000.00, $response->json('data.attributes.minSalary'));
        $this->assertEquals(75000.00, $response->json('data.attributes.maxSalary'));

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'title' => 'New Title',
            'min_salary' => 45000.00
        ]);
    }

    public function test_admin_can_update_salary_range(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create([
            'department_id' => $department->id,
            'min_salary' => 30000.00,
            'max_salary' => 50000.00
        ]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'attributes' => [
                'minSalary' => 40000.00,
                'maxSalary' => 65000.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertOk();
        $this->assertEquals(40000.00, $response->json('data.attributes.minSalary'));
        $this->assertEquals(65000.00, $response->json('data.attributes.maxSalary'));
    }

    public function test_admin_can_deactivate_position(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->active()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'attributes' => [
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.attributes.isActive'));
    }

    public function test_tech_user_can_update_position(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'attributes' => [
                'description' => 'Tech user updated description'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertOk();
        $this->assertEquals('Tech user updated description', $response->json('data.attributes.description'));
    }

    public function test_customer_user_cannot_update_position(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'attributes' => [
                'description' => 'Should not update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_position(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'attributes' => [
                'description' => 'Should not update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_position(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'positions',
            'id' => '99999',
            'attributes' => [
                'description' => 'Should not work'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch('/api/v1/positions/99999');

        $response->assertStatus(404);
    }

    public function test_validation_rules_apply_on_update(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'attributes' => [
                'minSalary' => 'invalid_salary'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_can_update_only_specific_fields(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create([
            'department_id' => $department->id,
            'title' => 'Original Title',
            'description' => 'Original description',
            'min_salary' => 30000.00,
            'is_active' => true
        ]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'attributes' => [
                'description' => 'Only updating description'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertOk();
        $this->assertEquals('Original Title', $response->json('data.attributes.title'));
        $this->assertEquals('Only updating description', $response->json('data.attributes.description'));
        $this->assertEquals(30000.00, $response->json('data.attributes.minSalary'));
        $this->assertTrue($response->json('data.attributes.isActive'));
    }

    public function test_can_change_department(): void
    {
        $admin = $this->getAdminUser();

        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department1->id]);

        $data = [
            'type' => 'positions',
            'id' => (string) $position->id,
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department2->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('positions')
            ->withData($data)
            ->patch("/api/v1/positions/{$position->id}");

        $response->assertOk();

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'department_id' => $department2->id
        ]);
    }
}

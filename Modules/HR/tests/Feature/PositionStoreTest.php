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
                'description' => 'Develop and maintain software applications',
                'level' => 'senior',
                'minSalary' => 40000.00,
                'maxSalary' => 70000.00,
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                    'level',
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
            'department_id' => $department->id,
            'is_active' => true
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
                'description' => 'Entry level position',
                'level' => 'junior',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'description' => 'Analyze business data',
                'level' => 'mid',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'level' => 'entry',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'level' => 'entry',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'description' => 'Missing title',
                'level' => 'entry',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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

    public function test_department_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'No Department Position',
                'level' => 'entry',
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

    public function test_level_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'positions',
            'attributes' => [
                'title' => 'Missing Level Position',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'level' => 'mid',
                'minSalary' => 'not_a_number',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'level' => 'mid',
                'maxSalary' => 'not_a_number',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'description' => 'Test default active',
                'level' => 'entry'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'description' => 'This position is not active',
                'level' => 'entry',
                'isActive' => false
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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
                'level' => 'entry',
                'isActive' => true
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ]
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

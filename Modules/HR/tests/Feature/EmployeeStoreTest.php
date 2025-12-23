<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class EmployeeStoreTest extends TestCase
{
    public function test_admin_can_create_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-001',
                'firstName' => 'John',
                'lastName' => 'Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1234567890',
                'hireDate' => '2024-01-15',
                'salary' => 55000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertCreated();
        $this->assertEquals('John', $response->json('data.attributes.firstName'));
        $this->assertEquals('Smith', $response->json('data.attributes.lastName'));
        $this->assertEquals(55000.00, $response->json('data.attributes.salary'));

        $this->assertDatabaseHas('employees', [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@example.com',
            'status' => 'active'
        ]);
    }

    public function test_tech_user_can_create_employee(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-002',
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'email' => 'jane.doe@example.com',
                'hireDate' => '2024-02-01',
                'salary' => 48000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertCreated();
        $this->assertEquals('Jane', $response->json('data.attributes.firstName'));
    }

    public function test_customer_user_cannot_create_employee(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-003',
                'firstName' => 'Should',
                'lastName' => 'NotCreate',
                'email' => 'test@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_employee(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-004',
                'firstName' => 'Guest',
                'lastName' => 'User',
                'email' => 'guest@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(401);
    }

    public function test_first_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-005',
                'lastName' => 'Smith',
                'email' => 'test@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_last_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-006',
                'firstName' => 'John',
                'email' => 'test@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_email_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-007',
                'firstName' => 'John',
                'lastName' => 'Smith',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_email_must_be_unique(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'email' => 'existing@example.com'
        ]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-008',
                'firstName' => 'John',
                'lastName' => 'Smith',
                'email' => 'existing@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_salary_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-009',
                'firstName' => 'John',
                'lastName' => 'Smith',
                'email' => 'test@example.com',
                'hireDate' => '2024-01-01',
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_salary_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-010',
                'firstName' => 'John',
                'lastName' => 'Smith',
                'email' => 'test@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 'not_a_number',
                'status' => 'active'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-011',
                'firstName' => 'John',
                'lastName' => 'Smith',
                'email' => 'test@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'invalid_status'
            ],
            'relationships' => [
                'department' => [
                    'data' => [
                        'type' => 'departments',
                        'id' => (string) $department->id
                    ]
                ],
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_department_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-012',
                'firstName' => 'John',
                'lastName' => 'Smith',
                'email' => 'test@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'active'
            ],
            'relationships' => [
                'position' => [
                    'data' => [
                        'type' => 'positions',
                        'id' => (string) $position->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }

    public function test_position_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();

        $data = [
            'type' => 'employees',
            'attributes' => [
                'employeeCode' => 'EMP-013',
                'firstName' => 'John',
                'lastName' => 'Smith',
                'email' => 'test@example.com',
                'hireDate' => '2024-01-01',
                'salary' => 40000.00,
                'status' => 'active'
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
            ->expects('employees')
            ->withData($data)
            ->post('/api/v1/employees');

        $response->assertStatus(422);
    }
}

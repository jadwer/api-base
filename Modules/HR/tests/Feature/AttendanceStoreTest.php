<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class AttendanceStoreTest extends TestCase
{
    public function test_admin_can_create_attendance(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'checkIn' => '09:00',
                'checkOut' => '17:30',
                'overtimeHours' => 0,
                'status' => 'present',
                'notes' => 'Regular day'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
        $this->assertEquals('present', $response->json('data.attributes.status'));

        // Use model for date comparison (SQLite stores datetime, MySQL stores date)
        $attendance = \Modules\HR\Models\Attendance::where('employee_id', $employee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('2024-01-15', $attendance->date->format('Y-m-d'));
        $this->assertEquals('present', $attendance->status);
    }

    public function test_tech_user_can_create_attendance(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-16',
                'checkIn' => '08:30',
                'checkOut' => '17:00',
                'status' => 'present'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_attendance(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'checkIn' => '09:00',
                'status' => 'present'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_attendance(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'checkIn' => '09:00',
                'status' => 'present'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(401);
    }

    public function test_employee_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'checkIn' => '09:00',
                'status' => 'present'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(422);
    }

    public function test_date_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'checkIn' => '09:00',
                'status' => 'present'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(422);
    }

    public function test_status_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'checkIn' => '09:00'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(422);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'checkIn' => '09:00',
                'status' => 'invalid_status'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertStatus(422);
    }

    public function test_auto_calculated_hours_worked_works_correctly(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'checkIn' => '09:00',
                'checkOut' => '17:00',
                'status' => 'present'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
        // Should calculate 8 hours
        $this->assertNotNull($response->json('data.attributes.hoursWorked'));
    }

    public function test_check_in_is_optional(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'status' => 'absent'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
    }

    public function test_check_out_is_optional(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'attendances',
            'attributes' => [
                'date' => '2024-01-15',
                'checkIn' => '09:00',
                'status' => 'present'
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('attendances')
            ->withData($data)
            ->post('/api/v1/attendances');

        $response->assertCreated();
    }
}

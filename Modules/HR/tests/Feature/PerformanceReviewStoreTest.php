<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PerformanceReview;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PerformanceReviewStoreTest extends TestCase
{
    public function test_admin_can_create_performance_review(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-01-15',
                'reviewPeriodStart' => '2024-01-01',
                'reviewPeriodEnd' => '2024-01-31',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 5,
                'attendanceRating' => 4,
                'comments' => 'Excellent performance this month',
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertCreated();
        $response->assertJsonFragment([
            'type' => 'performance-reviews',
        ]);

        $this->assertDatabaseHas('performance_reviews', [
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'overall_rating' => 4,
        ]);
    }

    public function test_admin_can_create_review_with_all_ratings(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-02-15',
                'reviewPeriodStart' => '2024-01-01',
                'reviewPeriodEnd' => '2024-01-31',
                'overallRating' => 3,
                'goalsRating' => 4,
                'skillsRating' => 3,
                'attendanceRating' => 5,
                'comments' => 'Good work overall',
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.overallRating', 3);
        $response->assertJsonPath('data.attributes.goalsRating', 4);
        $response->assertJsonPath('data.attributes.skillsRating', 3);
        $response->assertJsonPath('data.attributes.attendanceRating', 5);
    }

    public function test_admin_can_create_excellent_review(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-03-15',
                'reviewPeriodStart' => '2024-01-01',
                'reviewPeriodEnd' => '2024-03-31',
                'overallRating' => 5,
                'goalsRating' => 5,
                'skillsRating' => 5,
                'attendanceRating' => 5,
                'comments' => 'Outstanding performance',
                'status' => 'submitted',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertCreated();
        $this->assertDatabaseHas('performance_reviews', [
            'employee_id' => $employee->id,
            'overall_rating' => 5,
            'goals_rating' => 5,
            'skills_rating' => 5,
            'attendance_rating' => 5,
        ]);
    }

    public function test_admin_can_create_poor_review(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-04-15',
                'reviewPeriodStart' => '2024-01-01',
                'reviewPeriodEnd' => '2024-03-31',
                'overallRating' => 2,
                'goalsRating' => 2,
                'skillsRating' => 1,
                'attendanceRating' => 2,
                'comments' => 'Needs improvement in multiple areas',
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertCreated();
        $this->assertDatabaseHas('performance_reviews', [
            'employee_id' => $employee->id,
            'overall_rating' => 2,
            'goals_rating' => 2,
            'skills_rating' => 1,
            'attendance_rating' => 2,
        ]);
    }

    public function test_tech_user_can_create_performance_review(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-05-15',
                'reviewPeriodStart' => '2024-04-01',
                'reviewPeriodEnd' => '2024-04-30',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'comments' => 'Solid performance',
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_performance_review(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-06-15',
                'reviewPeriodStart' => '2024-05-01',
                'reviewPeriodEnd' => '2024-05-31',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_performance_review(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-07-15',
                'reviewPeriodStart' => '2024-06-01',
                'reviewPeriodEnd' => '2024-06-30',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(401);
    }

    public function test_employee_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-08-15',
                'reviewPeriodStart' => '2024-07-01',
                'reviewPeriodEnd' => '2024-07-31',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(422);
    }

    public function test_reviewer_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-09-15',
                'reviewPeriodStart' => '2024-08-01',
                'reviewPeriodEnd' => '2024-08-31',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
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
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(422);
    }

    public function test_review_date_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewPeriodStart' => '2024-09-01',
                'reviewPeriodEnd' => '2024-09-30',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(422);
    }

    public function test_review_period_dates_are_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-10-15',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(422);
    }

    public function test_all_ratings_are_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-11-15',
                'reviewPeriodStart' => '2024-10-01',
                'reviewPeriodEnd' => '2024-10-31',
                'overallRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(422);
    }

    public function test_ratings_must_be_between_1_and_5(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-12-15',
                'reviewPeriodStart' => '2024-11-01',
                'reviewPeriodEnd' => '2024-11-30',
                'overallRating' => 6,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(422);
    }

    public function test_review_period_end_must_be_after_or_equal_start(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-12-15',
                'reviewPeriodStart' => '2024-12-31',
                'reviewPeriodEnd' => '2024-12-01',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(422);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-12-15',
                'reviewPeriodStart' => '2024-12-01',
                'reviewPeriodEnd' => '2024-12-31',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'invalid-status',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertStatus(422);
    }

    public function test_comments_are_optional(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'attributes' => [
                'reviewDate' => '2024-12-20',
                'reviewPeriodStart' => '2024-12-01',
                'reviewPeriodEnd' => '2024-12-31',
                'overallRating' => 4,
                'goalsRating' => 4,
                'skillsRating' => 4,
                'attendanceRating' => 4,
                'status' => 'draft',
            ],
            'relationships' => [
                'employee' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $employee->id
                    ]
                ],
                'reviewer' => [
                    'data' => [
                        'type' => 'employees',
                        'id' => (string) $reviewer->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->post('/api/v1/performance-reviews');

        $response->assertCreated();
        $this->assertDatabaseHas('performance_reviews', [
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'comments' => null,
        ]);
    }
}

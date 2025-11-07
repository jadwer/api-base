<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PerformanceReview;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PerformanceReviewUpdateTest extends TestCase
{
    public function test_admin_can_update_performance_review(): void
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

        $review = PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'comments' => 'Initial comments',
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'comments' => 'Updated comments',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review->id,
            'comments' => 'Updated comments',
        ]);
    }

    public function test_admin_can_update_status(): void
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

        $review = PerformanceReview::factory()->draft()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'status' => 'submitted',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review->id,
            'status' => 'submitted',
        ]);
    }

    public function test_admin_can_update_ratings(): void
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

        $review = PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'overall_rating' => 3,
            'goals_rating' => 3,
            'skills_rating' => 3,
            'attendance_rating' => 3,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'overallRating' => 5,
                'goalsRating' => 5,
                'skillsRating' => 4,
                'attendanceRating' => 5,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review->id,
            'overall_rating' => 5,
            'goals_rating' => 5,
            'skills_rating' => 4,
            'attendance_rating' => 5,
        ]);
    }

    public function test_admin_can_update_review_dates(): void
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

        $review = PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'review_date' => '2024-01-15',
            'review_period_start' => '2024-01-01',
            'review_period_end' => '2024-01-31',
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'reviewDate' => '2024-02-15',
                'reviewPeriodStart' => '2024-02-01',
                'reviewPeriodEnd' => '2024-02-28',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review->id,
            'review_date' => '2024-02-15',
        ]);
    }

    public function test_admin_can_add_employee_comments(): void
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

        $review = PerformanceReview::factory()->reviewed()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'employee_comments' => null,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'employeeComments' => 'I acknowledge and understand this review',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review->id,
            'employee_comments' => 'I acknowledge and understand this review',
        ]);
    }

    public function test_tech_user_can_update_performance_review(): void
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

        $review = PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'comments' => 'Initial comments',
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'comments' => 'Tech updated comments',
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_update_performance_review(): void
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

        $review = PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'comments' => 'Unauthorized update',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_performance_review(): void
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

        $review = PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'comments' => 'Guest update attempt',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_review(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'performance-reviews',
            'id' => '99999',
            'attributes' => [
                'comments' => 'Update nonexistent',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch('/api/v1/performance-reviews/99999');

        $response->assertStatus(404);
    }

    public function test_ratings_must_be_valid_on_update(): void
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

        $review = PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'overallRating' => 10,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertStatus(422);
    }

    public function test_review_period_end_must_be_after_start_on_update(): void
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

        $review = PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'reviewPeriodStart' => '2024-12-31',
                'reviewPeriodEnd' => '2024-01-01',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertStatus(422);
    }

    public function test_can_transition_from_draft_to_submitted(): void
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

        $review = PerformanceReview::factory()->draft()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $this->assertEquals('draft', $review->status);

        $data = [
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
            'attributes' => [
                'status' => 'submitted',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->withData($data)
            ->patch("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review->id,
            'status' => 'submitted',
        ]);
    }
}

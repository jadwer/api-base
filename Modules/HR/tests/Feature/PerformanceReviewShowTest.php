<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PerformanceReview;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PerformanceReviewShowTest extends TestCase
{
    public function test_admin_can_view_performance_review(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
        ]);
    }

    public function test_tech_user_can_view_performance_review(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'type' => 'performance-reviews',
            'id' => (string) $review->id,
        ]);
    }

    public function test_customer_user_cannot_view_performance_review(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_performance_review(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_performance_review(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews/99999');

        $response->assertStatus(404);
    }

    public function test_can_include_employee_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}?include=employee");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'employee'
                ]
            ]
        ]);
    }

    public function test_can_include_reviewer_relationship(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}?include=reviewer");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'reviewer'
                ]
            ]
        ]);
    }

    public function test_can_view_all_rating_fields(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'overall_rating' => 4,
            'goals_rating' => 5,
            'skills_rating' => 3,
            'attendance_rating' => 4,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.overallRating', 4);
        $response->assertJsonPath('data.attributes.goalsRating', 5);
        $response->assertJsonPath('data.attributes.skillsRating', 3);
        $response->assertJsonPath('data.attributes.attendanceRating', 4);
    }

    public function test_can_view_draft_review(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->draft()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.status', 'draft');
    }

    public function test_can_view_submitted_review(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id,
        ]);

        $review = PerformanceReview::factory()->submitted()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews/{$review->id}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.status', 'submitted');
    }
}

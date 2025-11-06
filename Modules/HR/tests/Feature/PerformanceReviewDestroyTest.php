<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PerformanceReview;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PerformanceReviewDestroyTest extends TestCase
{
    public function test_admin_can_delete_performance_review(): void
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
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_admin_can_delete_draft_review(): void
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
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review->id,
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_delete_submitted_review(): void
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
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review->id,
            'status' => 'submitted',
        ]);
    }

    public function test_admin_can_delete_reviewed_review(): void
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

        $review = PerformanceReview::factory()->reviewed()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review->id,
            'status' => 'reviewed',
        ]);
    }

    public function test_admin_can_delete_acknowledged_review(): void
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

        $review = PerformanceReview::factory()->acknowledged()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review->id,
            'status' => 'acknowledged',
        ]);
    }

    public function test_tech_user_can_delete_performance_review(): void
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
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_customer_user_cannot_delete_performance_review(): void
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
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_guest_cannot_delete_performance_review(): void
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
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_returns_404_for_nonexistent_review(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->delete('/api/v1/performance-reviews/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_review_does_not_affect_other_reviews(): void
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

        $review1 = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);
        $review2 = PerformanceReview::factory()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->delete("/api/v1/performance-reviews/{$review1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review1->id,
        ]);
        $this->assertDatabaseHas('performance_reviews', [
            'id' => $review2->id,
        ]);
    }

    public function test_can_delete_review_with_excellent_ratings(): void
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

        $review = PerformanceReview::factory()->excellent()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review->id,
            'overall_rating' => 5,
        ]);
    }

    public function test_can_delete_review_with_poor_ratings(): void
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

        $review = PerformanceReview::factory()->poor()->create([
            'employeeId' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->delete("/api/v1/performance-reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('performance_reviews', [
            'id' => $review->id,
            'overall_rating' => 2,
        ]);
    }
}

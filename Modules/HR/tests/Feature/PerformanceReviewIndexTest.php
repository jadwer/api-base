<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PerformanceReview;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PerformanceReviewIndexTest extends TestCase
{
    public function test_admin_can_list_performance_reviews(): void
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

        PerformanceReview::factory()->count(3)->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_performance_reviews_by_review_date(): void
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

        PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'review_date' => '2024-01-15',
        ]);
        PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
            'review_date' => '2024-03-20',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews?sort=reviewDate&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        $testReviews = collect($data)->filter(function($item) {
            $reviewDate = $item['attributes']['reviewDate'] ?? null;
            return str_starts_with($reviewDate, '2024-01-15') || str_starts_with($reviewDate, '2024-03-20');
        })->values();

        $this->assertCount(2, $testReviews);
        $this->assertStringContainsString('2024-01-15', $testReviews[0]['attributes']['reviewDate']);
        $this->assertStringContainsString('2024-03-20', $testReviews[1]['attributes']['reviewDate']);
    }

    public function test_admin_can_filter_performance_reviews_by_status(): void
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

        PerformanceReview::factory()->count(2)->draft()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);
        PerformanceReview::factory()->count(1)->submitted()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews?filter[status]=draft');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_performance_reviews_by_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee1 = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $employee2 = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $reviewer = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        PerformanceReview::factory()->count(2)->create([
            'employee_id' => $employee1->id,
            'reviewer_id' => $reviewer->id,
        ]);
        PerformanceReview::factory()->count(1)->create([
            'employee_id' => $employee2->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get("/api/v1/performance-reviews?filter[employeeId]={$employee1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_performance_reviews_with_permission(): void
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

        PerformanceReview::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_performance_reviews(): void
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

        PerformanceReview::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_performance_reviews(): void
    {
        $response = $this->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews');

        $response->assertStatus(401);
    }

    public function test_can_paginate_performance_reviews(): void
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

        PerformanceReview::factory()->count(25)->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        $this->assertArrayHasKey('page', $response->json('meta'));
    }

    public function test_can_include_employee_and_reviewer_relationships(): void
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

        PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('performance-reviews')
            ->get('/api/v1/performance-reviews?include=employee,reviewer');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'employee',
                        'reviewer'
                    ]
                ]
            ]
        ]);
    }
}

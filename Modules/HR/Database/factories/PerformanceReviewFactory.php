<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\PerformanceReview;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

class PerformanceReviewFactory extends Factory
{
    protected $model = PerformanceReview::class;

    public function definition(): array
    {
        $reviewDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $periodEnd = Carbon::instance($reviewDate)->subDay();
        $periodStart = $periodEnd->copy()->subMonths(6);

        return [
            'employee_id' => Employee::factory(),
            'reviewer_id' => Employee::factory(),
            'review_date' => $reviewDate,
            'review_period_start' => $periodStart,
            'review_period_end' => $periodEnd,
            'overall_rating' => $this->faker->numberBetween(1, 5),
            'goals_rating' => $this->faker->numberBetween(1, 5),
            'skills_rating' => $this->faker->numberBetween(1, 5),
            'attendance_rating' => $this->faker->numberBetween(1, 5),
            'comments' => $this->faker->paragraph(),
            'employee_comments' => $this->faker->optional(0.5)->paragraph(),
            'status' => $this->faker->randomElement(['draft', 'submitted', 'reviewed', 'acknowledged']),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'employee_comments' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewed',
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'acknowledged',
            'employee_comments' => $this->faker->paragraph(),
        ]);
    }

    public function forEmployee(int $employeeId): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_id' => $employeeId,
        ]);
    }

    public function byReviewer(int $reviewerId): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewer_id' => $reviewerId,
        ]);
    }

    public function excellent(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_rating' => 5,
            'goals_rating' => 5,
            'skills_rating' => 5,
            'attendance_rating' => 5,
        ]);
    }

    public function poor(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_rating' => 2,
            'goals_rating' => 2,
            'skills_rating' => 2,
            'attendance_rating' => 2,
        ]);
    }

    public function thisQuarter(): static
    {
        $periodEnd = now();
        $periodStart = now()->subMonths(3);

        return $this->state(fn (array $attributes) => [
            'review_date' => now(),
            'review_period_start' => $periodStart,
            'review_period_end' => $periodEnd,
        ]);
    }
}

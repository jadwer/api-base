<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Leave;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveType;
use Carbon\Carbon;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+30 days');
        $endDate = Carbon::instance($startDate)->addDays($this->faker->numberBetween(1, 10));
        $daysRequested = Carbon::instance($startDate)->diffInDays($endDate) + 1;

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $daysRequested,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'cancelled']),
            'reason' => $this->faker->sentence(),
            'approver_id' => null,
            'approved_at' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Pending leave state.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approver_id' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Approved leave state.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approver_id' => Employee::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Rejected leave state.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approver_id' => Employee::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Cancelled leave state.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Leave for a specific employee.
     */
    public function forEmployee(int $employeeId): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_id' => $employeeId,
        ]);
    }

    /**
     * Leave for a specific leave type.
     */
    public function forLeaveType(int $leaveTypeId): static
    {
        return $this->state(fn (array $attributes) => [
            'leave_type_id' => $leaveTypeId,
        ]);
    }

    /**
     * Leave with an approver.
     */
    public function withApprover(int $approverId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'approver_id' => $approverId ?? Employee::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Leave this week.
     */
    public function thisWeek(): static
    {
        $startDate = now()->addDays(rand(1, 3));
        $endDate = $startDate->copy()->addDays(rand(1, 3));

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $startDate->diffInDays($endDate) + 1,
        ]);
    }

    /**
     * Leave this month.
     */
    public function thisMonth(): static
    {
        $startDate = now()->addDays(rand(5, 15));
        $endDate = $startDate->copy()->addDays(rand(1, 7));

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $startDate->diffInDays($endDate) + 1,
        ]);
    }

    /**
     * Leave starting today.
     */
    public function startingToday(): static
    {
        $startDate = now();
        $endDate = $startDate->copy()->addDays(rand(1, 5));

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $startDate->diffInDays($endDate) + 1,
        ]);
    }

    /**
     * One-day leave.
     */
    public function oneDay(): static
    {
        $date = now()->addDays(rand(1, 10));

        return $this->state(fn (array $attributes) => [
            'start_date' => $date,
            'end_date' => $date,
            'days_requested' => 1,
        ]);
    }

    /**
     * Long leave (more than 5 days).
     */
    public function longLeave(): static
    {
        $startDate = now()->addDays(rand(10, 20));
        $endDate = $startDate->copy()->addDays(rand(10, 30));

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $startDate->diffInDays($endDate) + 1,
        ]);
    }
}

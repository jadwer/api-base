<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\LeaveType;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Vacation',
                'Sick Leave',
                'Personal Leave',
                'Unpaid Leave',
                'Maternity Leave',
                'Paternity Leave',
                'Bereavement Leave',
                'Study Leave',
            ]),
            'code' => strtoupper($this->faker->unique()->lexify('??')) . $this->faker->unique()->numerify('##'),
            'description' => $this->faker->sentence(),
            'days_allowed' => $this->faker->numberBetween(5, 30),
            'requires_approval' => $this->faker->boolean(80), // 80% require approval
            'paid' => $this->faker->boolean(70), // 70% are paid
            'active' => true,
        ];
    }

    /**
     * Vacation leave type.
     */
    public function vacation(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Vacation',
            'code' => 'VAC',
            'description' => 'Annual vacation leave',
            'days_allowed' => 15,
            'requires_approval' => true,
            'paid' => true,
            'active' => true,
        ]);
    }

    /**
     * Sick leave type.
     */
    public function sickLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Sick Leave',
            'code' => 'SICK',
            'description' => 'Medical sick leave',
            'days_allowed' => 10,
            'requires_approval' => true,
            'paid' => true,
            'active' => true,
        ]);
    }

    /**
     * Personal leave type.
     */
    public function personalLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Personal Leave',
            'code' => 'PERS',
            'description' => 'Personal leave for family matters',
            'days_allowed' => 5,
            'requires_approval' => true,
            'paid' => true,
            'active' => true,
        ]);
    }

    /**
     * Unpaid leave type.
     */
    public function unpaidLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Unpaid Leave',
            'code' => 'UNPAID',
            'description' => 'Leave without pay',
            'days_allowed' => 30,
            'requires_approval' => true,
            'paid' => false,
            'active' => true,
        ]);
    }

    /**
     * Maternity leave type.
     */
    public function maternityLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Maternity Leave',
            'code' => 'MAT',
            'description' => 'Maternity leave for mothers',
            'days_allowed' => 90,
            'requires_approval' => true,
            'paid' => true,
            'active' => true,
        ]);
    }

    /**
     * Paternity leave type.
     */
    public function paternityLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Paternity Leave',
            'code' => 'PAT',
            'description' => 'Paternity leave for fathers',
            'days_allowed' => 15,
            'requires_approval' => true,
            'paid' => true,
            'active' => true,
        ]);
    }

    /**
     * Active leave type.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Inactive leave type.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Paid leave type.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid' => true,
        ]);
    }

    /**
     * Unpaid leave type state.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid' => false,
        ]);
    }

    /**
     * Leave type requiring approval.
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
        ]);
    }

    /**
     * Leave type not requiring approval.
     */
    public function noApprovalRequired(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => false,
        ]);
    }
}

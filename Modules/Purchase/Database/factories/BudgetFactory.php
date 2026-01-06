<?php

namespace Modules\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Purchase\Models\Budget;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $startDate = now()->startOfMonth();
        $budgetedAmount = $this->faker->randomFloat(2, 10000, 500000);

        return [
            'name' => $this->faker->words(3, true) . ' Budget',
            'code' => strtoupper($this->faker->unique()->lexify('BUD-????-') . now()->format('Ym')),
            'description' => $this->faker->optional()->sentence(),
            'budget_type' => Budget::TYPE_GENERAL,
            'period_type' => Budget::PERIOD_MONTHLY,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->endOfMonth(),
            'fiscal_year' => now()->year,
            'budgeted_amount' => $budgetedAmount,
            'committed_amount' => $this->faker->randomFloat(2, 0, $budgetedAmount * 0.3),
            'spent_amount' => $this->faker->randomFloat(2, 0, $budgetedAmount * 0.4),
            'warning_threshold' => 80.00,
            'critical_threshold' => 95.00,
            'hard_limit' => false,
            'allow_overcommit' => false,
            'is_active' => true,
        ];
    }

    /**
     * Department budget state.
     */
    public function forDepartment(string $departmentCode): static
    {
        return $this->state(fn () => [
            'budget_type' => Budget::TYPE_DEPARTMENT,
            'department_code' => $departmentCode,
        ]);
    }

    /**
     * Category budget state.
     */
    public function forCategory(int $categoryId): static
    {
        return $this->state(fn () => [
            'budget_type' => Budget::TYPE_CATEGORY,
            'category_id' => $categoryId,
        ]);
    }

    /**
     * Supplier budget state.
     */
    public function forSupplier(int $contactId): static
    {
        return $this->state(fn () => [
            'budget_type' => Budget::TYPE_SUPPLIER,
            'contact_id' => $contactId,
        ]);
    }

    /**
     * Quarterly budget state.
     */
    public function quarterly(): static
    {
        $startDate = now()->startOfQuarter();
        return $this->state(fn () => [
            'period_type' => Budget::PERIOD_QUARTERLY,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->endOfQuarter(),
        ]);
    }

    /**
     * Annual budget state.
     */
    public function annual(): static
    {
        $startDate = now()->startOfYear();
        return $this->state(fn () => [
            'period_type' => Budget::PERIOD_ANNUAL,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->endOfYear(),
        ]);
    }

    /**
     * Hard limit budget state.
     */
    public function hardLimit(): static
    {
        return $this->state(fn () => [
            'hard_limit' => true,
            'allow_overcommit' => false,
        ]);
    }

    /**
     * Over warning threshold state.
     */
    public function overWarning(): static
    {
        return $this->state(function (array $attributes) {
            $budgeted = $attributes['budgeted_amount'] ?? 100000;
            return [
                'committed_amount' => $budgeted * 0.50,
                'spent_amount' => $budgeted * 0.35,
            ];
        });
    }

    /**
     * Over critical threshold state.
     */
    public function overCritical(): static
    {
        return $this->state(function (array $attributes) {
            $budgeted = $attributes['budgeted_amount'] ?? 100000;
            return [
                'committed_amount' => $budgeted * 0.50,
                'spent_amount' => $budgeted * 0.47,
            ];
        });
    }

    /**
     * Exceeded budget state.
     */
    public function exceeded(): static
    {
        return $this->state(function (array $attributes) {
            $budgeted = $attributes['budgeted_amount'] ?? 100000;
            return [
                'committed_amount' => $budgeted * 0.60,
                'spent_amount' => $budgeted * 0.50,
            ];
        });
    }

    /**
     * Inactive budget state.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * Expired budget state.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->subMonths(2)->startOfMonth(),
            'end_date' => now()->subMonth()->endOfMonth(),
        ]);
    }
}

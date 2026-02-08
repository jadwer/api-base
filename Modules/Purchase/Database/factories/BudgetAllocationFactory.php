<?php

namespace Modules\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Purchase\Models\BudgetAllocation;
use Modules\Purchase\Models\Budget;
use Modules\Purchase\Models\PurchaseOrder;

class BudgetAllocationFactory extends Factory
{
    protected $model = BudgetAllocation::class;

    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'allocated_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'status' => BudgetAllocation::STATUS_COMMITTED,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function committed(): static
    {
        return $this->state(fn () => [
            'status' => BudgetAllocation::STATUS_COMMITTED,
        ]);
    }

    public function spent(): static
    {
        return $this->state(fn () => [
            'status' => BudgetAllocation::STATUS_SPENT,
        ]);
    }

    public function released(): static
    {
        return $this->state(fn () => [
            'status' => BudgetAllocation::STATUS_RELEASED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => BudgetAllocation::STATUS_CANCELLED,
        ]);
    }
}

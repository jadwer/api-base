<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\ARReceipt;

class ARReceiptFactory extends Factory
{
    protected $model = ARReceipt::class;

    public function definition(): array
    {
        return [
            'contact_id' => $this->faker->numberBetween(1, 100),
            'receipt_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'payment_method' => $this->faker->sentence(3),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'bank_account_id' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
        ];
    }

    /**
     * Active ARReceipt
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive ARReceipt
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}

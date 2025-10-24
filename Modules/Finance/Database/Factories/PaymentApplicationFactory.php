<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationFactory extends Factory
{
    protected $model = PaymentApplication::class;

    public function definition(): array
    {
        return [
            'payment_id' => $this->faker->numberBetween(1, 100),
            'ar_invoice_id' => $this->faker->numberBetween(1, 100),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'application_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'is_active' => $this->faker->boolean(70),
        ];
    }

    /**
     * Active PaymentApplication
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Inactive PaymentApplication
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\Payment;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_number' => $this->faker->sentence(3),
            'payment_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'customer_id' => 1, // TODO: Use existing Customer ID - \Modules\Sales\Models\Customer::inRandomOrder()->first()?->id ?? 1,
            'bank_account_id' => $this->faker->numberBetween(1, 100),
            'payment_method_id' => $this->faker->numberBetween(1, 100),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'applied_amount' => $this->faker->randomFloat(2, 1, 1000),
            'unapplied_amount' => $this->faker->randomFloat(2, 1, 1000),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'journal_entry_id' => $this->faker->numberBetween(1, 100),
            'reference' => $this->faker->sentence(3),
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'is_active' => true,
        ];
    }

    /**
     * Active Payment
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive Payment
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}

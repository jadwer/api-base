<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\APPayment;

class APPaymentFactory extends Factory
{
    protected $model = APPayment::class;

    public function definition(): array
    {
        return [
            'contact_id' => \Modules\Contacts\Models\Contact::factory()->supplier(),
            'payment_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'payment_method' => $this->faker->sentence(3),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'bank_account_id' => \Modules\Finance\Models\BankAccount::factory(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
        ];
    }

    /**
     * Active APPayment
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive APPayment
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}

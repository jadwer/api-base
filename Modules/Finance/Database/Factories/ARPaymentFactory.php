<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\ARPayment;
use Modules\Contacts\Models\Contact;
use Modules\Accounting\Models\FiscalPeriod;

class ARPaymentFactory extends Factory
{
    protected $model = ARPayment::class;

    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-' . $this->faker->unique()->numerify('######'),
            'payment_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'contact_id' => Contact::factory()->customer(),
            'fiscal_period_id' => FiscalPeriod::factory(),
            'payment_method' => $this->faker->randomElement(['cash', 'check', 'transfer', 'card']),
            'currency' => 'MXN',
            'payment_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'applied_amount' => 0,
            'unapplied_amount' => fn (array $attrs) => $attrs['payment_amount'],
            'status' => 'draft',
            'reference' => $this->faker->optional(0.7)->word(),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the payment is posted.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'posted',
        ]);
    }

    /**
     * Indicate that the payment is voided.
     */
    public function voided(): static
    {
        return $this->state(function (array $attributes) {
            $user = \Modules\User\Models\User::factory()->create();
            return [
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by_id' => $user->id,
                'void_reason' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Indicate that the payment is fully applied.
     */
    public function fullyApplied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'posted',
            'applied_amount' => $attributes['payment_amount'],
            'unapplied_amount' => 0,
        ]);
    }

    /**
     * Indicate a specific payment amount.
     */
    public function amount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_amount' => $amount,
            'unapplied_amount' => $amount,
        ]);
    }
}

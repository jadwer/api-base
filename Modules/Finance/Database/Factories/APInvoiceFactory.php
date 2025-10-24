<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\APInvoice;

class APInvoiceFactory extends Factory
{
    protected $model = APInvoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->sentence(3),
            'invoice_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'due_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'supplier_id' => 1, // TODO: Use existing Supplier ID - \Modules\Purchase\Models\Supplier::inRandomOrder()->first()?->id ?? 1,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'subtotal' => $this->faker->randomFloat(2, 1, 1000),
            'tax_amount' => $this->faker->randomFloat(2, 1, 1000),
            'total_amount' => $this->faker->randomFloat(2, 1, 1000),
            'paid_amount' => $this->faker->randomFloat(2, 1, 1000),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'journal_entry_id' => $this->faker->numberBetween(1, 100),
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'is_active' => $this->faker->boolean(70),
        ];
    }

    /**
     * Active APInvoice
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive APInvoice
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}

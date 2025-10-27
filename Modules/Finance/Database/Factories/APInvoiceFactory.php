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
            'invoice_number' => 'AP-' . $this->faker->unique()->numerify('####'),
            'invoice_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'due_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'supplier_id' => 1, // Temporary - Supplier model doesn't exist yet, using dummy ID
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'subtotal' => $this->faker->randomFloat(2, 100, 1000),
            'tax_amount' => $this->faker->randomFloat(2, 10, 100),
            'total_amount' => $this->faker->randomFloat(2, 110, 1100),
            'paid_amount' => $this->faker->randomFloat(2, 0, 500),
            'status' => $this->faker->randomElement(['pending', 'paid', 'overdue']),
            'journal_entry_id' => null, // Nullable - will be set when needed
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'metadata' => $this->faker->optional(0.5)->passthrough(['department' => $this->faker->word, 'ref' => $this->faker->uuid]),
            'is_active' => true,
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

<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\ARInvoice;
use Modules\Contacts\Models\Contact;

class ARInvoiceFactory extends Factory
{
    protected $model = ARInvoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->unique()->numerify('AR-#####'),
            'invoice_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'due_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'contact_id' => Contact::where('is_customer', true)->inRandomOrder()->first()?->id ?? Contact::factory()->customer()->create()->id,
            'sales_order_id' => null,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'subtotal' => $this->faker->randomFloat(2, 1, 1000),
            'tax_amount' => $this->faker->randomFloat(2, 1, 1000),
            'total_amount' => $this->faker->randomFloat(2, 1, 1000),
            'paid_amount' => $this->faker->randomFloat(2, 1, 1000),
            'status' => $this->faker->randomElement(['draft', 'posted', 'void']),
            'journal_entry_id' => null,
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'metadata' => [],
            'is_active' => true,
        ];
    }

    /**
     * Active ARInvoice
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive ARInvoice
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}

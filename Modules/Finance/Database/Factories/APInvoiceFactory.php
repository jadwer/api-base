<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\APInvoice;
use Modules\Contacts\Models\Contact;

class APInvoiceFactory extends Factory
{
    protected $model = APInvoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->unique()->numerify('AP-#####'),
            'invoice_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'due_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'contact_id' => Contact::where('is_supplier', true)->inRandomOrder()->first()?->id ?? Contact::factory()->supplier()->create()->id,
            'purchase_order_id' => null,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'subtotal' => $this->faker->randomFloat(2, 100, 1000),
            'tax_amount' => $this->faker->randomFloat(2, 10, 100),
            'total_amount' => $this->faker->randomFloat(2, 110, 1100),
            'paid_amount' => $this->faker->randomFloat(2, 0, 500),
            'status' => $this->faker->randomElement(['draft', 'posted', 'void']),
            'journal_entry_id' => null,
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'metadata' => [],
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

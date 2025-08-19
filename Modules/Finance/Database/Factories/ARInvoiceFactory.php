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
            'contact_id' => Contact::factory()->customer(),
            'invoice_number' => $this->faker->sentence(3),
            'invoice_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'due_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'exchange_rate' => $this->faker->randomFloat(2, 0, 100),
            'subtotal' => $this->faker->randomFloat(2, 1, 1000),
            'tax_total' => $this->faker->randomFloat(2, 1, 1000),
            'total' => $this->faker->randomFloat(2, 1, 1000),
            'status' => $this->faker->randomElement(['draft', 'posted', 'paid']),
        ];
    }

    /**
     * Draft ARInvoice
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Posted ARInvoice
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'posted',
        ]);
    }

    /**
     * Paid ARInvoice
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }
}

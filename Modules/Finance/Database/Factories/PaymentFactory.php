<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\PaymentMethod;
use Modules\Contacts\Models\Contact;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_number' => $this->faker->unique()->numerify('PAY-#####'),
            'payment_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'contact_id' => Contact::where('is_customer', true)->inRandomOrder()->first()?->id ?? Contact::factory()->customer()->create()->id,
            'bank_account_id' => BankAccount::inRandomOrder()->first()?->id ?? BankAccount::factory()->create()->id,
            'payment_method_id' => PaymentMethod::inRandomOrder()->first()?->id ?? PaymentMethod::factory()->create()->id,
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'applied_amount' => $this->faker->randomFloat(2, 1, 1000),
            'unapplied_amount' => $this->faker->randomFloat(2, 1, 1000),
            'status' => $this->faker->randomElement(['unapplied', 'partial', 'fully_applied']),
            'journal_entry_id' => null,
            'reference' => $this->faker->optional(0.7)->numerify('REF-#####'),
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'metadata' => [],
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

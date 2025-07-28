<?php

namespace Modules\Sales\Database\Factories;

use Modules\Sales\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $classifications = ['mayorista', 'minorista', 'especial'];
        $classification = $this->faker->randomElement($classifications);
        
        // Ajustar límite de crédito según clasificación
        $creditLimitRanges = [
            'mayorista' => [25000, 100000],
            'especial' => [50000, 200000],
            'minorista' => [5000, 25000]
        ];
        
        $range = $creditLimitRanges[$classification];
        $creditLimit = $this->faker->randomFloat(2, $range[0], $range[1]);
        $currentCredit = $this->faker->randomFloat(2, 0, $creditLimit * 0.8);
        
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional(0.8)->phoneNumber(),
            'address' => $this->faker->optional(0.7)->streetAddress(),
            'city' => $this->faker->optional(0.7)->city(),
            'state' => $this->faker->optional(0.6)->state(),
            'country' => $this->faker->optional(0.8)->country(),
            'classification' => $classification,
            'credit_limit' => $creditLimit,
            'current_credit' => $currentCredit,
            'is_active' => $this->faker->boolean(85), // 85% activos
            'metadata' => $this->faker->optional(0.6)->passthrough([
                'source' => $this->faker->randomElement(['web', 'mobile', 'phone', 'referral']),
                'preferences' => [
                    'payment_method' => $this->faker->randomElement(['cash', 'credit', 'bank_transfer', 'check']),
                    'delivery_preference' => $this->faker->randomElement(['morning', 'afternoon', 'any_time'])
                ],
                'notes' => $this->faker->optional(0.3)->sentence()
            ]),
        ];
    }

    /**
     * Indicate that the customer is a wholesale customer.
     */
    public function mayorista(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'mayorista',
            'credit_limit' => $this->faker->randomFloat(2, 25000, 100000),
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the customer is a retail customer.
     */
    public function minorista(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'minorista',
            'credit_limit' => $this->faker->randomFloat(2, 5000, 25000),
        ]);
    }

    /**
     * Indicate that the customer is a special customer.
     */
    public function especial(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'especial',
            'credit_limit' => $this->faker->randomFloat(2, 50000, 200000),
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the customer has reached credit limit.
     */
    public function creditLimitReached(): static
    {
        return $this->state(function (array $attributes) {
            $creditLimit = $this->faker->randomFloat(2, 10000, 50000);
            return [
                'credit_limit' => $creditLimit,
                'current_credit' => $creditLimit,
            ];
        });
    }

    /**
     * Indicate that the customer has no credit.
     */
    public function noCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => 0.00,
            'current_credit' => 0.00,
        ]);
    }

    /**
     * Indicate that the customer has complete information.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'metadata' => [
                'source' => 'web',
                'preferences' => [
                    'payment_method' => 'credit',
                    'delivery_preference' => 'morning',
                    'communication_language' => 'es'
                ],
                'notes' => 'Customer with complete profile information',
                'registration_date' => now()->toDateString(),
                'sales_rep' => $this->faker->name()
            ],
        ]);
    }

    /**
     * Indicate that the customer has minimal information.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'country' => null,
            'metadata' => null,
        ]);
    }
}

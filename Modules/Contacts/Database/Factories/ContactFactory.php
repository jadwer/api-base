<?php

namespace Modules\Contacts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Models\Contact;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        $isCustomer = $this->faker->boolean(60);
        $isSupplier = $isCustomer ? $this->faker->boolean(30) : true; // Ensure at least one is true
        
        $creditLimit = $isCustomer ? $this->faker->randomFloat(2, 1000, 100000) : 0.00;
        $currentCredit = $isCustomer ? $this->faker->randomFloat(2, 0, $creditLimit * 0.8) : 0.00; // Max 80% of limit
        
        return [
            'type' => $this->faker->randomElement(['person', 'company']),
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->optional(0.8)->company(),
            'tax_id' => $this->faker->optional(0.7)->regexify('[A-Z]{3}[0-9]{6}[A-Z0-9]{3}'),
            'email' => $this->faker->optional(0.8)->safeEmail(),
            'phone' => $this->faker->optional(0.9)->phoneNumber(),
            'website' => $this->faker->optional(0.3)->url(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'is_customer' => $isCustomer,
            'is_supplier' => $isSupplier,
            'credit_limit' => $creditLimit,
            'current_credit' => $currentCredit,
            'classification' => $this->faker->optional(0.7)->randomElement(['premium', 'standard', 'basic']),
            'payment_terms' => $this->faker->randomElement([15, 30, 45, 60, 90]),
            'notes' => $this->faker->optional(0.4)->paragraph(),
            'metadata' => $this->faker->optional(0.3)->passthrough([
                'source' => $this->faker->randomElement(['web', 'phone', 'referral', 'marketing']),
                'industry' => $this->faker->randomElement(['manufacturing', 'retail', 'services', 'technology']),
                'size' => $this->faker->randomElement(['small', 'medium', 'large', 'enterprise'])
            ]),
        ];
    }

    /**
     * Active Contact
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive Contact
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Customer Contact
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_customer' => true,
            'is_supplier' => false,
            'credit_limit' => $this->faker->randomFloat(2, 10000, 200000),
            'payment_terms' => $this->faker->randomElement([30, 45, 60]),
        ]);
    }

    /**
     * Supplier Contact
     */
    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_customer' => false,
            'is_supplier' => true,
            'credit_limit' => 0.00,
            'payment_terms' => $this->faker->randomElement([15, 30, 45]),
        ]);
    }

    /**
     * Mixed Customer and Supplier Contact
     */
    public function mixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_customer' => true,
            'is_supplier' => true,
            'credit_limit' => $this->faker->randomFloat(2, 15000, 100000),
        ]);
    }
}

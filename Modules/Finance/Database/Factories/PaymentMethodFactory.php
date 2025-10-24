<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\PaymentMethod;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->lexify('????##'),
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->sentence(3),
            'requires_reference' => $this->faker->boolean(70),
            'is_active' => $this->faker->boolean(70),
        ];
    }

    /**
     * Active PaymentMethod
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Inactive PaymentMethod
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

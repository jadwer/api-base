<?php

namespace Modules\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Purchase\Models\Supplier;

class SupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'rfc' => $this->faker->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'is_active' => $this->faker->boolean(85), // 85% activos
        ];
    }

    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

<?php

namespace Modules\Inventory\Database\Factories;

use Modules\Inventory\Models\WarehouseLocation;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseLocationFactory extends Factory
{
    protected $model = WarehouseLocation::class;

    public function definition(): array
    {
        static $counter = 1;
        
        return [
            'warehouse_id' => Warehouse::factory(),
            'name' => $this->faker->randomElement(['Section A', 'Section B', 'Aisle']) . '-' . $counter++,
            'code' => 'LOC' . str_pad($counter, 4, '0', STR_PAD_LEFT),
            'location_type' => $this->faker->randomElement(['shelf', 'rack', 'floor', 'bin', 'zone']),
            'aisle' => $this->faker->optional()->regexify('[A-Z][0-9]{1,2}'),
            'rack' => $this->faker->optional()->regexify('[0-9]{1,3}'),
            'shelf' => $this->faker->optional()->regexify('[0-9]{1,2}'),
            'bin' => $this->faker->optional()->regexify('[A-Z]{1,2}'),
            'barcode' => $this->faker->optional()->ean13(),
            'is_active' => $this->faker->boolean(90), // 90% active
            'max_weight' => $this->faker->optional()->numberBetween(100, 5000),
            'max_volume' => $this->faker->optional()->numberBetween(1, 100),
            'length' => $this->faker->optional()->randomFloat(2, 1, 10),
            'width' => $this->faker->optional()->randomFloat(2, 1, 10),
            'height' => $this->faker->optional()->randomFloat(2, 1, 5),
            'temperature_controlled' => $this->faker->boolean(20),
            'humidity_controlled' => $this->faker->boolean(15),
            'access_level' => $this->faker->randomElement(['public', 'restricted', 'high_security']),
            'priority' => $this->faker->numberBetween(1, 5),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the location is temperature controlled.
     */
    public function temperatureControlled(): static
    {
        return $this->state(fn (array $attributes) => [
            'temperature_controlled' => true,
            'access_level' => 'restricted',
        ]);
    }

    /**
     * Indicate that the location is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 5,
            'access_level' => 'high_security',
        ]);
    }
}

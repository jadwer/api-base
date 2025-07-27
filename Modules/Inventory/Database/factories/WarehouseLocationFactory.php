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
        $code = 'LOC-' . str_pad($counter++, 4, '0', STR_PAD_LEFT);
        
        return [
            'warehouse_id' => Warehouse::factory(),
            'name' => 'Ubicación ' . $code,
            'code' => $code,
            'description' => 'Descripción de ubicación ' . $code,
            'location_type' => ['aisle', 'rack', 'shelf', 'bin', 'zone', 'bay'][array_rand(['aisle', 'rack', 'shelf', 'bin', 'zone', 'bay'])],
            'aisle' => 'A' . rand(1, 10),
            'rack' => 'R' . rand(1, 20),
            'shelf' => 'S' . rand(1, 5),
            'level' => 'L' . rand(1, 3),
            'position' => rand(1, 50),
            'barcode' => 'BC' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
            'max_weight' => round(rand(1000, 50000) / 10, 2), // Genera decimales como 150.50
            'max_volume' => round(rand(100, 10000) / 10, 2), // Genera decimales como 75.20
            'dimensions' => rand(1, 5) . 'x' . rand(1, 5) . 'x' . rand(1, 3),
            'is_active' => true,
            'is_pickable' => true,
            'is_receivable' => true,
            'priority' => rand(1, 10),
            'metadata' => [
                'zone' => 'Z' . rand(1, 5),
                'access_level' => ['public', 'restricted', 'high_security'][array_rand(['public', 'restricted', 'high_security'])],
                'notes' => 'Ubicación creada por factory'
            ],
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'is_pickable' => false,
            'is_receivable' => false,
        ]);
    }

    /**
     * Indicate that the location is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 10,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'access_level' => 'high_security'
            ]),
        ]);
    }

    /**
     * Create a specific type of location.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => $type,
        ]);
    }
}

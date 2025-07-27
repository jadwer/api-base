<?php

namespace Modules\Inventory\Database\Factories;

use Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        static $counter = 1;
        
        $name = 'Test Warehouse ' . $counter;
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . $counter,
            'code' => 'WH' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'description' => 'Test warehouse description',
            'address' => 'Test Address ' . rand(100, 999),
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'postal_code' => '12345',
            'phone' => '+1234567890',
            'email' => 'test@warehouse.com',
            'manager_name' => 'Test Manager',
            'is_active' => true,
            'warehouse_type' => 'main',
            'max_capacity' => rand(1000, 50000), // Always integer
            'capacity_unit' => 'm3',
            'operating_hours' => [
                'monday' => '08:00-17:00',
                'tuesday' => '08:00-17:00', 
                'wednesday' => '08:00-17:00',
                'thursday' => '08:00-17:00',
                'friday' => '08:00-17:00',
                'saturday' => '08:00-12:00',
                'sunday' => 'closed'
            ],
            'metadata' => [
                'security_level' => 'standard',
                'climate_control' => true,
                'loading_docks' => rand(1, 5)
            ],
        ];
    }

    /**
     * Indicate that the warehouse is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the warehouse has high capacity.
     */
    public function highCapacity(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_capacity' => $this->faker->numberBetween(30000, 100000),
            'warehouse_type' => 'main',
        ]);
    }
}

<?php

namespace Modules\Inventory\Database\Factories;

use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBatchFactory extends Factory
{
    protected $model = ProductBatch::class;

    public function definition(): array
    {
        $initialQuantity = round($this->faker->randomFloat(4, 50, 1000), 4);
        $consumedQuantity = round($this->faker->randomFloat(4, 0, $initialQuantity * 0.7), 4);
        $currentQuantity = round($initialQuantity - $consumedQuantity, 4);
        $reservedQuantity = round($this->faker->randomFloat(4, 0, min(20, $currentQuantity)), 4);
        $unitCost = round($this->faker->randomFloat(4, 5, 200), 4);
        
        $manufacturingDate = $this->faker->dateTimeBetween('-2 years', '-1 month');
        $expirationDate = $this->faker->dateTimeBetween('now', '+3 years');
        
        return [
            'product_id' => \Modules\Product\Models\Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_location_id' => $this->faker->boolean(80) ? WarehouseLocation::factory() : null,
            'batch_number' => $this->faker->regexify('[A-Z]{3}[0-9]{6}'),
            'lot_number' => $this->faker->optional()->regexify('LOT[0-9]{8}'),
            'manufacturing_date' => $manufacturingDate,
            'expiration_date' => $expirationDate,
            'best_before_date' => $this->faker->optional()->dateTimeBetween($manufacturingDate, $expirationDate),
            'initial_quantity' => $initialQuantity,
            'current_quantity' => $currentQuantity,
            'reserved_quantity' => $reservedQuantity,
            'unit_cost' => $unitCost,
            'status' => $this->faker->randomElement(['active', 'expired', 'quarantine', 'recalled', 'consumed']),
            'supplier_name' => $this->faker->optional()->company,
            'supplier_batch' => $this->faker->optional()->regexify('SUP[0-9]{8}'),
            'quality_notes' => $this->faker->optional()->sentence(),
            'test_results' => $this->faker->optional()->randomElement([
                null,
                ['ph' => $this->faker->randomFloat(2, 6, 8)],
                [
                    'ph' => $this->faker->randomFloat(2, 6, 8),
                    'moisture' => $this->faker->randomFloat(2, 5, 15),
                    'quality_grade' => $this->faker->randomElement(['A', 'B', 'C'])
                ]
            ]),
            'certifications' => $this->faker->optional()->randomElement([
                null,
                ['ISO9001' => true],
                ['ISO9001' => true, 'HACCP' => true],
                ['ISO9001' => true, 'HACCP' => true, 'Organic' => true]
            ]),
            'metadata' => $this->faker->optional()->randomElement([
                null,
                ['inspector' => $this->faker->name],
                [
                    'inspector' => $this->faker->name,
                    'inspection_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
                    'temperature_log' => 'maintained'
                ]
            ]),
        ];
    }

    /**
     * Indicate that the batch is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expiration_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            'current_quantity' => $this->faker->numberBetween(10, 100), // Still has quantity but expired
        ]);
    }

    /**
     * Indicate that the batch is in quarantine.
     */
    public function quarantine(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'quarantine',
            'quality_notes' => 'Batch under quality review - potential contamination detected',
            'test_results' => [
                'quality_grade' => 'FAIL',
                'contamination_detected' => true,
                'inspector_notes' => 'Requires further testing'
            ],
        ]);
    }

    /**
     * Indicate that the batch is near expiration.
     */
    public function nearExpiration(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'expiration_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Indicate that the batch is fully consumed.
     */
    public function consumed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'consumed',
            'current_quantity' => 0,
            'reserved_quantity' => 0,
        ]);
    }
}

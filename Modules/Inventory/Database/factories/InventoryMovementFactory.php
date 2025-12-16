<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Product\Models\Product;
use Modules\User\Models\User;

class InventoryMovementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = InventoryMovement::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $movementType = $this->faker->randomElement(['entry', 'exit', 'transfer', 'adjustment']);
        $quantity = $this->faker->randomFloat(4, 1, 500);
        
        // Make exits negative
        if ($movementType === 'exit') {
            $quantity = -$quantity;
        }

        return [
            'movement_type' => $movementType,
            'reference_type' => $this->getRandomReferenceType($movementType),
            'reference_id' => $this->faker->optional(0.7)->numberBetween(1, 1000),
            'movement_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'description' => $this->faker->optional(0.6)->sentence(),
            
            // Relaciones (se establecerán en el seeder)
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_location_id' => null, // Opcional
            'destination_warehouse_id' => null, // Solo para transfers
            'destination_location_id' => null, // Solo para transfers
            'user_id' => User::factory(),
            
            // Cantidades y costos
            'quantity' => $quantity,
            'unit_cost' => $this->faker->randomFloat(2, 5, 200),
            
            // Estado
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
            'previous_stock' => $this->faker->optional(0.8)->randomFloat(4, 0, 1000),
            'new_stock' => $this->faker->optional(0.8)->randomFloat(4, 0, 1000),
            
            // Campos JSON
            'batch_info' => $this->generateBatchInfo(),
            'metadata' => $this->generateMetadata(),
        ];
    }

    /**
     * Generate realistic reference type based on movement type
     */
    private function getRandomReferenceType(string $movementType): string
    {
        $referenceTypes = match($movementType) {
            'entry' => ['purchase', 'adjustment', 'transfer', 'manual'],
            'exit' => ['sale', 'adjustment', 'transfer', 'manual'],
            'transfer' => ['transfer'],
            'adjustment' => ['adjustment', 'manual'],
            default => ['manual']
        };

        return $this->faker->randomElement($referenceTypes);
    }

    /**
     * Generate realistic batch info
     */
    private function generateBatchInfo(): array
    {
        if ($this->faker->boolean(30)) { // 30% chance of having batch info
            return [
                'batch_number' => $this->faker->regexify('BATCH-[0-9]{6}'),
                'expiry_date' => $this->faker->dateTimeBetween('+6 months', '+2 years')->format('Y-m-d'),
                'manufacturing_date' => $this->faker->dateTimeBetween('-1 year', '-1 month')->format('Y-m-d'),
            ];
        }
        
        return [];
    }

    /**
     * Generate realistic metadata
     */
    private function generateMetadata(): array
    {
        $metadata = [];
        
        if ($this->faker->boolean(40)) {
            $metadata['source'] = $this->faker->randomElement(['automatic', 'manual', 'import']);
        }
        
        if ($this->faker->boolean(30)) {
            $metadata['notes'] = $this->faker->sentence();
        }
        
        if ($this->faker->boolean(20)) {
            $metadata['temperature'] = $this->faker->randomFloat(1, -5, 25);
            $metadata['humidity'] = $this->faker->randomFloat(1, 30, 80);
        }
        
        return $metadata;
    }

    /**
     * Indicate that the movement is an entry.
     */
    public function entry(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'entry',
            'reference_type' => $this->faker->randomElement(['purchase', 'adjustment', 'manual']),
            'quantity' => abs($attributes['quantity']), // Always positive
        ]);
    }

    /**
     * Indicate that the movement is an exit.
     */
    public function exit(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'exit',
            'reference_type' => $this->faker->randomElement(['sale', 'adjustment', 'manual']),
            'quantity' => -abs($attributes['quantity']), // Always negative
        ]);
    }

    /**
     * Indicate that the movement is a transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'transfer',
            'reference_type' => 'transfer',
            'quantity' => abs($attributes['quantity']), // Always positive
            'destination_warehouse_id' => Warehouse::factory(),
        ]);
    }

    /**
     * Indicate that the movement is an adjustment.
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'adjustment',
            'reference_type' => 'adjustment',
            'description' => 'Ajuste de inventario físico',
        ]);
    }

    /**
     * Indicate that the movement is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'previous_stock' => $this->faker->randomFloat(4, 50, 500),
            'new_stock' => max(0, $attributes['previous_stock'] + $attributes['quantity']),
        ]);
    }

    /**
     * Indicate that the movement is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'previous_stock' => null,
            'new_stock' => null,
        ]);
    }

    /**
     * Indicate that the movement is for a specific product.
     */
    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Indicate that the movement is for a specific warehouse.
     */
    public function forWarehouse(int $warehouseId): static
    {
        return $this->state(fn (array $attributes) => [
            'warehouse_id' => $warehouseId,
        ]);
    }

    /**
     * Indicate that the movement is by a specific user.
     */
    public function byUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Indicate that the movement has been quality checked.
     * Required for exit and transfer movements that are completed.
     */
    public function qualityChecked(): static
    {
        return $this->state(fn (array $attributes) => [
            'quality_checked' => true,
            'quality_checked_at' => now(),
            'quality_checked_by' => $attributes['user_id'] ?? null,
        ]);
    }
}
<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Product\Models\Product;
use Modules\User\Models\User;

class InventoryMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar que existan las dependencias
        if (Product::count() === 0) {
            $this->command->warn('No hay productos disponibles. Ejecuta primero el seeder de Product module.');
            return;
        }

        if (Warehouse::count() === 0) {
            $this->command->warn('No hay warehouses disponibles. Ejecuta primero WarehouseSeeder.');
            return;
        }

        if (User::count() === 0) {
            $this->command->warn('No hay usuarios disponibles. Ejecuta primero UserSeeder.');
            return;
        }

        // Obtener entidades existentes
        $products = Product::limit(10)->get();
        $warehouses = Warehouse::limit(3)->get();
        $users = User::limit(5)->get();
        $adminUser = User::where('email', 'admin@example.com')->first() ?? $users->first();

        // Crear movimientos de ejemplo para cada tipo
        $this->createEntryMovements($products, $warehouses, $adminUser);
        $this->createExitMovements($products, $warehouses, $adminUser);
        $this->createTransferMovements($products, $warehouses, $adminUser);
        $this->createAdjustmentMovements($products, $warehouses, $adminUser);

        // Crear movimientos aleatorios adicionales
        $this->createRandomMovements($products, $warehouses, $users);

        $this->command->info('Inventory movement records seeded successfully.');
    }

    /**
     * Create entry movements (recepciones, compras)
     */
    private function createEntryMovements($products, $warehouses, $adminUser): void
    {
        foreach ($products->take(5) as $product) {
            foreach ($warehouses->take(2) as $warehouse) {
                // Entrada por compra
                InventoryMovement::factory()
                    ->entry()
                    ->completed()
                    ->create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'user_id' => $adminUser->id,
                        'reference_type' => 'purchase',
                        'reference_id' => fake()->numberBetween(1, 100),
                        'description' => "Recepción de compra - {$product->name}",
                        'quantity' => fake()->numberBetween(10, 100),
                        'unit_cost' => fake()->randomFloat(2, 10, 50),
                        'movement_date' => fake()->dateTimeBetween('-6 months', '-1 month'),
                    ]);

                // Entrada por ajuste positivo
                if (fake()->boolean(40)) {
                    InventoryMovement::factory()
                        ->entry()
                        ->completed()
                        ->create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouse->id,
                            'user_id' => $adminUser->id,
                            'reference_type' => 'adjustment',
                            'description' => "Ajuste positivo - Stock encontrado en inventario físico",
                            'quantity' => fake()->numberBetween(1, 20),
                            'unit_cost' => fake()->randomFloat(2, 10, 50),
                            'movement_date' => fake()->dateTimeBetween('-3 months', 'now'),
                        ]);
                }
            }
        }
    }

    /**
     * Create exit movements (ventas, desperdicios)
     */
    private function createExitMovements($products, $warehouses, $adminUser): void
    {
        foreach ($products->take(5) as $product) {
            foreach ($warehouses->take(2) as $warehouse) {
                // Salida por venta
                InventoryMovement::factory()
                    ->exit()
                    ->completed()
                    ->qualityChecked() // Required for exit movements
                    ->create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'user_id' => $adminUser->id,
                        'reference_type' => 'sale',
                        'reference_id' => fake()->numberBetween(1, 50),
                        'description' => "Venta - {$product->name}",
                        'quantity' => fake()->numberBetween(1, 30),
                        'unit_cost' => fake()->randomFloat(2, 10, 50),
                        'movement_date' => fake()->dateTimeBetween('-2 months', 'now'),
                    ]);

                // Salida por desperdicio
                if (fake()->boolean(30)) {
                    InventoryMovement::factory()
                        ->exit()
                        ->completed()
                        ->qualityChecked() // Required for exit movements
                        ->create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouse->id,
                            'user_id' => $adminUser->id,
                            'reference_type' => 'adjustment',
                            'description' => "Producto dañado - Baja por desperdicio",
                            'quantity' => fake()->numberBetween(1, 10),
                            'unit_cost' => fake()->randomFloat(2, 10, 50),
                            'movement_date' => fake()->dateTimeBetween('-1 month', 'now'),
                            'metadata' => [
                                'reason' => 'damaged',
                                'condition' => 'expired',
                            ],
                        ]);
                }
            }
        }
    }

    /**
     * Create transfer movements between warehouses
     */
    private function createTransferMovements($products, $warehouses, $adminUser): void
    {
        if ($warehouses->count() < 2) {
            return; // Need at least 2 warehouses for transfers
        }

        foreach ($products->take(3) as $product) {
            $sourceWarehouse = $warehouses->first();
            $destinationWarehouse = $warehouses->last();

            // Transferencia entre almacenes
            InventoryMovement::factory()
                ->transfer()
                ->completed()
                ->qualityChecked() // Required for transfer movements
                ->create([
                    'product_id' => $product->id,
                    'warehouse_id' => $sourceWarehouse->id,
                    'destination_warehouse_id' => $destinationWarehouse->id,
                    'user_id' => $adminUser->id,
                    'reference_type' => 'transfer',
                    'reference_id' => fake()->numberBetween(1, 20),
                    'description' => "Transferencia {$sourceWarehouse->name} → {$destinationWarehouse->name}",
                    'quantity' => fake()->numberBetween(5, 50),
                    'unit_cost' => fake()->randomFloat(2, 10, 50),
                    'movement_date' => fake()->dateTimeBetween('-2 months', 'now'),
                ]);
        }
    }

    /**
     * Create adjustment movements (inventario físico)
     */
    private function createAdjustmentMovements($products, $warehouses, $adminUser): void
    {
        foreach ($products->take(4) as $product) {
            $warehouse = $warehouses->random();

            // Ajuste de inventario físico (puede ser positivo o negativo)
            $adjustmentType = fake()->randomElement(['positive', 'negative']);
            $quantity = fake()->numberBetween(1, 15);
            
            if ($adjustmentType === 'negative') {
                $quantity = -$quantity;
            }

            InventoryMovement::factory()
                ->adjustment()
                ->completed()
                ->create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $adminUser->id,
                    'reference_type' => 'adjustment',
                    'description' => "Ajuste por inventario físico - {$adjustmentType}",
                    'quantity' => $quantity,
                    'unit_cost' => fake()->randomFloat(2, 10, 50),
                    'movement_date' => fake()->dateTimeBetween('-1 month', 'now'),
                    'metadata' => [
                        'adjustment_type' => $adjustmentType,
                        'physical_count_date' => fake()->dateTimeBetween('-1 week', 'now')->format('Y-m-d'),
                        'counted_by' => fake()->name(),
                    ],
                ]);
        }
    }

    /**
     * Create random movements for variety
     */
    private function createRandomMovements($products, $warehouses, $users): void
    {
        // Crear movimientos aleatorios adicionales (solo entradas completadas para evitar validación)
        foreach (range(1, 30) as $i) {
            $product = $products->random();
            $warehouse = $warehouses->random();
            $user = $users->random();

            InventoryMovement::factory()
                ->entry()
                ->completed()
                ->create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $user->id,
                    'movement_date' => fake()->dateTimeBetween('-6 months', 'now'),
                ]);
        }

        // Crear algunos movimientos pendientes (no requieren quality check)
        foreach (range(1, 10) as $i) {
            $product = $products->random();
            $warehouse = $warehouses->random();
            $user = $users->random();

            InventoryMovement::factory()
                ->pending()
                ->create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $user->id,
                    'movement_date' => fake()->dateTimeBetween('now', '+1 week'),
                    'description' => 'Movimiento programado',
                ]);
        }
    }
}
<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Product\Models\Product;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar que existan products y warehouses
        if (Product::count() === 0) {
            $this->command->warn('No hay productos disponibles. Ejecuta primero el seeder de Product module.');
            return;
        }

        if (Warehouse::count() === 0) {
            $this->command->warn('No hay warehouses disponibles. Ejecuta primero WarehouseSeeder.');
            return;
        }

        // Obtener productos y warehouses existentes
        $products = Product::limit(10)->get();
        $warehouses = Warehouse::with('locations')->get();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                // Crear stock en diferentes locations del warehouse
                $locations = $warehouse->locations->take(2); // Máximo 2 locations por warehouse
                
                foreach ($locations as $location) {
                    Stock::factory()->create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'warehouse_location_id' => $location->id,
                        'quantity' => fake()->numberBetween(10, 500),
                        'reserved_quantity' => fake()->numberBetween(0, 50),
                        'minimum_stock' => fake()->numberBetween(5, 20),
                        'maximum_stock' => fake()->numberBetween(100, 1000),
                    ]);
                }

                // También crear algunos stocks sin location específica
                if (fake()->boolean(30)) { // 30% probabilidad
                    Stock::factory()->create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'warehouse_location_id' => null,
                        'quantity' => fake()->numberBetween(5, 100),
                        'reserved_quantity' => 0,
                        'minimum_stock' => fake()->numberBetween(1, 10),
                        'maximum_stock' => fake()->numberBetween(50, 200),
                    ]);
                }
            }
        }

        $this->command->info('Stock records seeded successfully.');
    }
}
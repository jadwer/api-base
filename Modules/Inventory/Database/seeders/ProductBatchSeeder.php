<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\ProductBatch;
use Modules\Product\Models\Product;

class ProductBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar que existan productos
        if (Product::count() === 0) {
            $this->command->warn('No hay productos disponibles. Ejecuta primero el seeder de Product module.');
            return;
        }

        // Obtener productos existentes
        $products = Product::limit(15)->get();

        foreach ($products as $product) {
            // Crear entre 1-4 batches por producto
            $batchCount = fake()->numberBetween(1, 4);
            
            for ($i = 0; $i < $batchCount; $i++) {
                // Generar fechas realistas
                $manufacturedDate = fake()->dateTimeBetween('-2 years', '-1 month');
                $expirationDate = fake()->dateTimeBetween('+6 months', '+3 years');
                
                $initialQuantity = fake()->numberBetween(100, 1000);
                $currentQuantity = fake()->numberBetween(10, $initialQuantity);
                
                ProductBatch::factory()->create([
                    'product_id' => $product->id,
                    'batch_number' => $this->generateBatchNumber($product->id, $i + 1),
                    'manufacturing_date' => $manufacturedDate,
                    'expiration_date' => $expirationDate,
                    'initial_quantity' => $initialQuantity,
                    'current_quantity' => $currentQuantity,
                    'reserved_quantity' => fake()->numberBetween(0, min(10, $currentQuantity)),
                    'unit_cost' => fake()->randomFloat(2, 10, 500),
                    'supplier_batch' => fake()->boolean(70) ? fake()->regexify('SUP[0-9]{8}') : null,
                    'quality_notes' => fake()->boolean(40) ? fake()->sentence() : null,
                    'status' => fake()->randomElement(['active', 'expired', 'quarantine', 'recalled', 'consumed']),
                ]);
            }
        }

        $this->command->info('Product batch records seeded successfully.');
    }

    /**
     * Generate a realistic batch number
     */
    private function generateBatchNumber(int $productId, int $sequence): string
    {
        $year = date('Y');
        $month = str_pad(fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
        $productCode = str_pad($productId, 3, '0', STR_PAD_LEFT);
        $sequenceCode = str_pad($sequence, 2, '0', STR_PAD_LEFT);
        
        return "BATCH-{$year}{$month}-{$productCode}-{$sequenceCode}";
    }
}
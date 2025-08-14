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
                
                ProductBatch::factory()->create([
                    'product_id' => $product->id,
                    'batch_number' => $this->generateBatchNumber($product->id, $i + 1),
                    'manufacturing_date' => $manufacturedDate,
                    'expiration_date' => $expirationDate,
                    'quantity' => fake()->numberBetween(50, 1000),
                    'unit_cost' => fake()->randomFloat(2, 10, 500),
                    'supplier_batch_number' => fake()->boolean(70) ? fake()->regexify('[A-Z]{2}[0-9]{6}') : null,
                    'notes' => fake()->boolean(40) ? fake()->sentence() : null,
                    'status' => fake()->randomElement(['active', 'expired', 'recalled']),
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
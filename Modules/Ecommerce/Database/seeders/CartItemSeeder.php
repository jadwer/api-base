<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\CartItem;
use Modules\Product\Models\Product;

class CartItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding CartItem...');
        
        // Get existing Product records
        $products = \Modules\Product\Models\Product::all();
        
        if ($products->isEmpty()) {
            $this->command->warn('No Product records found. Skipping product_id seeding.');
            return;
        }

        // Create sample CartItem records
        // Create CartItem records using existing Product records
        $products->take(5)->each(function ($parent) {
            CartItem::factory()
                ->count(rand(1, 3))
                ->create(['product_id' => $parent->id]);
        });

        
        $this->command->info('✅ CartItem seeded successfully!');
    }
}

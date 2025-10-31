<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;

class EcommerceDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding Ecommerce module...');
        
        $this->call([
            PermissionsSeeder::class,
            CurrencySeeder::class,
            ShippingMethodSeeder::class,
            ShoppingCartSeeder::class,
            CartItemSeeder::class,
            CouponSeeder::class,
        ]);
        
        $this->command->info('🎉 Ecommerce module seeded successfully!');
    }
}

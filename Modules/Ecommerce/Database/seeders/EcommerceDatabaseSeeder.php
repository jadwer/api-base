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
            EcommerceAssignPermissionsSeeder::class,
            CurrencySeeder::class,        // ✅ Multi-currency support (ESSENTIAL)
            ShippingMethodSeeder::class,  // ✅ Shipping methods (ESSENTIAL)
            // ❌ DEMO DATA - Commented for presentation
            // ShoppingCartSeeder::class,   // Sample shopping carts
            // CartItemSeeder::class,       // Sample cart items
            // CouponSeeder::class,         // Sample coupons
        ]);

        $this->command->info('🎉 Ecommerce module seeded successfully!');
    }
}

<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\Coupon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Coupon...');
        
        // Create sample Coupon records
        Coupon::factory()->count(10)->create();

        
        $this->command->info('✅ Coupon seeded successfully!');
    }
}

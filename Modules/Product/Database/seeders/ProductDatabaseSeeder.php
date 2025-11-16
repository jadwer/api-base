<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;

class ProductDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ProductPermissionSeeder::class,
            ProductAssignPermissionsSeeder::class,
            UnitSeeder::class,     // ✅ Units of measure (ESSENTIAL)
            BrandSeeder::class,    // ✅ Base brands (ESSENTIAL)
            CategorySeeder::class, // ✅ Product categories (ESSENTIAL)
            // ❌ DEMO DATA - Commented for presentation
            // ProductSeeder::class, // Sample products (100+ items)
        ]);
    }
}

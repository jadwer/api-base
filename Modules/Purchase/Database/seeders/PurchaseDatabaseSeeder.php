<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;

class PurchaseDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PurchasePermissionSeeder::class,
            PurchaseAssignPermissionsSeeder::class,
            // ✅ DEMO DATA - Enabled for presentation
            PurchaseOrderSeeder::class, // Sample purchase orders
        ]);
    }
}

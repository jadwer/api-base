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
            // ⚠️ TODO: Review if PurchaseOrderItemPermissionSeeder is permissions or demo data
            // PurchaseOrderItemPermissionSeeder::class,
            // ❌ DEMO DATA - Commented for presentation
            // PurchaseOrderSeeder::class, // Sample purchase orders
        ]);
    }
}

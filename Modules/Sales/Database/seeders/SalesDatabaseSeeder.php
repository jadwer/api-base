<?php

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SalesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        // Ejecutar seeders en orden de dependencias
        $this->call([
            SalesPermissionSeeder::class,
            SalesAssignPermissionsSeeder::class,
            // ❌ DEMO DATA - Commented for presentation
            // SalesOrderSeeder::class, // Sample sales orders
        ]);
    }
}

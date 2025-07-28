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
            // CustomerSeeder::class, // Se puede agregar después
            // SalesOrderSeeder::class, // Se puede agregar después
        ]);
    }
}

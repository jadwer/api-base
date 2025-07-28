<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;

class PurchaseModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SupplierSeeder::class,
            PurchaseOrderSeeder::class,
        ]);
    }
}

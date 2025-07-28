<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Purchase\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::factory()->count(10)->create();
    }
}

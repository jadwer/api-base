<?php

namespace Modules\SatCatalogs\Database\Seeders;

use Illuminate\Database\Seeder;

class SatCatalogsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SatCatalogsSeeder::class,
        ]);
    }
}

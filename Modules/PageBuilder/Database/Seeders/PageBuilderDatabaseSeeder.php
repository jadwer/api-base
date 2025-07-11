<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;

class PageBuilderDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PagePermissionSeeder::class,
            PageAssignPermissionsSeeder::class,
            PageSeeder::class,
        ]);
    }
}

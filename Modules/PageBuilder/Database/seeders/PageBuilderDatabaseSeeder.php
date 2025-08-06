<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

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
        Log::info('PageBuilderDatabaseSeeder executed successfully.');
    }
}

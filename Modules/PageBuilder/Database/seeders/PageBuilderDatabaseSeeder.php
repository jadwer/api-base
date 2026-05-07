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
            // DemoPagesSeeder is opt-in. Run manually for local QA:
            //   php artisan db:seed --class=Modules\\PageBuilder\\Database\\Seeders\\DemoPagesSeeder
            // Tenants ship their own pages seeder (e.g. AcmePagesSeeder).
        ]);
        Log::info('PageBuilderDatabaseSeeder executed successfully.');
    }
}

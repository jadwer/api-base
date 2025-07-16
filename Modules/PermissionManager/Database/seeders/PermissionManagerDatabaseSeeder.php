<?php

namespace Modules\PermissionManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PermissionManagerDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AssignPermissionsSeeder::class,
        ]);
        Log::info('PermissionManagerDatabaseSeeder executed successfully.');
    }
}

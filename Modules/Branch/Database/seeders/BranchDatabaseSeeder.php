<?php

namespace Modules\Branch\Database\Seeders;

use Illuminate\Database\Seeder;

class BranchDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchPermissionSeeder::class,
            BranchAssignPermissionsSeeder::class,
        ]);
    }
}

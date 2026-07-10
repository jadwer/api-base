<?php

namespace Modules\Commissions\Database\Seeders;

use Illuminate\Database\Seeder;

class CommissionsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CommissionsPermissionSeeder::class,
            CommissionsAssignPermissionsSeeder::class,
        ]);
    }
}

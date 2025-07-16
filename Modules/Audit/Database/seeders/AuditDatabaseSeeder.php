<?php

namespace Modules\Audit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AuditDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AuditPermissionSeeder::class,
            AuditAssignPermissionsSeeder::class,
        ]);
        Log::info('AuditDatabaseSeeder executed successfully.');
    }
}

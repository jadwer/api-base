<?php

namespace Modules\SystemHealth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemHealthPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define SystemHealth permissions
        $permissions = [
            'system-health.index',      // View overall health status
            'system-health.database',   // View database metrics
            'system-health.storage',    // View storage metrics
            'system-health.queue',      // View queue status
            'system-health.errors',     // View error logs
            'system-health.metrics',    // View application metrics
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api']
            );
        }

        // Assign all permissions to god and admin roles
        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();

        if ($godRole) {
            $godRole->givePermissionTo($permissions);
        }

        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $this->command->info('SystemHealth permissions created and assigned to god/admin roles.');
    }
}

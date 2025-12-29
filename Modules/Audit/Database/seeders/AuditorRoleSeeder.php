<?php

namespace Modules\Audit\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuditorRoleSeeder extends Seeder
{
    /**
     * Create the auditor role with read-only access to audit and system health.
     */
    public function run(): void
    {
        // Create the auditor role
        $auditor = Role::firstOrCreate(
            ['name' => 'auditor', 'guard_name' => 'api'],
            ['description' => 'Auditor con acceso de solo lectura a logs de auditoría y salud del sistema']
        );

        // Permissions for Audit module (read-only)
        $auditPermissions = [
            'audit.index',
            'audit.show',
            'audit.export',
        ];

        // Permissions for SystemHealth module (read-only)
        $systemHealthPermissions = [
            'system-health.index',
            'system-health.database',
            'system-health.storage',
            'system-health.queue',
            'system-health.errors',
            'system-health.metrics',
        ];

        // Combine all permissions
        $allPermissions = array_merge($auditPermissions, $systemHealthPermissions);

        // Assign permissions to auditor role
        foreach ($allPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'api')
                ->first();

            if ($permission) {
                $auditor->givePermissionTo($permission);
            }
        }

        $this->command->info("Rol 'auditor' creado con permisos de auditoría y system health.");
    }
}

<?php

namespace Modules\Audit\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuditAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $god = Role::where('name', 'god')->first();

        if ($god) {
            $this->command->warn("Asignando permisos al rol {$god->name}, sin sobrescribir los existentes...");
            $permissions = Permission::where('name', 'like', 'audit.%')->get();
            $god->givePermissionTo($permissions);
        }

        // Ejemplo adicional: también podrías asignar algunos permisos a admin
        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo([
                'audit.index',
                'audit.show',
                'audit.export',
            ]);
        }
    }
}

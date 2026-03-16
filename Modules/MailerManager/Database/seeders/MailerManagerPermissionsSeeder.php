<?php

namespace Modules\MailerManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MailerManagerPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'email-templates.index',
            'email-templates.show',
            'email-templates.store',
            'email-templates.update',
            'email-templates.destroy',
            'system-emails.index',
            'system-emails.show',
            'system-emails.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
            );
        }

        // God/Admin get all permissions
        $allPermissions = Permission::where('guard_name', 'api')
            ->whereIn('name', $permissions)
            ->get();

        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        if ($godRole) {
            $godRole->givePermissionTo($allPermissions);
        }

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($allPermissions);
        }

        // Tech gets read-only access
        $readOnlyPermissions = Permission::where('guard_name', 'api')
            ->whereIn('name', [
                'email-templates.index',
                'email-templates.show',
                'system-emails.index',
                'system-emails.show',
            ])
            ->get();

        $techRole = Role::where('name', 'tech')->where('guard_name', 'api')->first();
        if ($techRole) {
            $techRole->givePermissionTo($readOnlyPermissions);
        }
    }
}

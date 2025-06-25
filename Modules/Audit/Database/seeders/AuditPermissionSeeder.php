<?php

namespace Modules\Audit\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;


class AuditPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'audit.index',
            'audit.show',
            'audit.store',
            'audit.update',
            'audit.destroy',
            'audit.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }
    }
}

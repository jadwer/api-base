<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;


class PagePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'page.index',
            'page.show',
            'page.store',
            'page.update',
            'page.destroy',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }
    }
}

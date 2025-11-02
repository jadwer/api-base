<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class PagePermissionSeeder extends Seeder
{
    use BulkPermissions;

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

        $this->bulkCreatePermissions($permissions);
    }
}

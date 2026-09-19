<?php

namespace Modules\Branch\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class BranchPermissionSeeder extends Seeder
{
    use BulkPermissions;

    public function run(): void
    {
        $permissions = [
            'branches.index', 'branches.show', 'branches.store', 'branches.update', 'branches.destroy',
        ];

        $this->bulkCreatePermissions($permissions);
    }
}

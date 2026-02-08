<?php

namespace Modules\Audit\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class AuditPermissionSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'audit.index',
            'audit.show',
            'audit.export',
        ];

        $this->bulkCreatePermissions($permissions);
    }
}

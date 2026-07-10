<?php

namespace Modules\Commissions\Database\Seeders;

use App\Database\Seeders\Concerns\BulkPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CommissionsPermissionSeeder extends Seeder
{
    use BulkPermissions;

    public function run(): void
    {
        $permissions = [
            'commissions.index',
            'commissions.show',
            'commissions.update',
            'commissions.pay',
        ];

        $this->bulkCreatePermissions($permissions);
        Log::info('Commissions permissions created successfully.');
    }
}

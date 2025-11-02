<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;
use Modules\PermissionManager\Models\Role;
use App\Database\Seeders\Concerns\BulkPermissions;

class PurchaseOrderItemPermissionSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions using bulk insert
        $permissions = [
            'purchase-order-items.index',
            'purchase-order-items.show',
            'purchase-order-items.store',
            'purchase-order-items.update',
            'purchase-order-items.destroy',
        ];

        // Use bulk method to create and assign to roles
        $this->bulkCreateAndAssignPermissions($permissions, ['god', 'admin']);

        // Assign roles to users
        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();

        if ($godRole) {
            $godUsers = User::whereIn('email', ['system@audit.local', 'god@example.com'])->get();
            foreach ($godUsers as $user) {
                if (!$user->hasRole($godRole)) {
                    $user->assignRole($godRole);
                }
            }
        }

        if ($adminRole) {
            $adminUsers = User::whereIn('email', ['admin@example.com'])->get();
            foreach ($adminUsers as $user) {
                if (!$user->hasRole($adminRole)) {
                    $user->assignRole($adminRole);
                }
            }
        }

        Log::info('PurchaseOrderItem permissions and role assignments completed successfully.');
    }
}

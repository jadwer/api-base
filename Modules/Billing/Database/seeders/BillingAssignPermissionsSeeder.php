<?php

namespace Modules\Billing\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class BillingAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        $techRole = Role::where('name', 'tech')->where('guard_name', 'api')->first();
        $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();

        // God gets all billing permissions
        if ($godRole) {
            $allPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'billing.%')
                ->get();
            $godRole->givePermissionTo($allPermissions);
        }

        // Admin gets all billing permissions
        if ($adminRole) {
            $adminPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'billing.%')
                ->get();
            $adminRole->givePermissionTo($adminPermissions);
        }

        // Tech gets read-only billing permissions
        if ($techRole) {
            $techPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'billing.%')
                ->where(function ($query) {
                    $query->where('name', 'like', '%.index')
                          ->orWhere('name', 'like', '%.show')
                          ->orWhere('name', 'like', '%.download-%')
                          ->orWhere('name', 'like', '%.preview-%');
                })
                ->get();
            $techRole->givePermissionTo($techPermissions);
        }

        // Customer gets limited billing permissions (can view/download their own invoices)
        if ($customerRole) {
            $customerPermissions = Permission::where('guard_name', 'api')
                ->whereIn('name', [
                    // CFDI Invoices - can list/view/download their own invoices (Authorizer filters by ownership)
                    'billing.cfdi-invoices.index',
                    'billing.cfdi-invoices.show',
                    'billing.cfdi-invoices.download-xml',
                    'billing.cfdi-invoices.download-pdf',
                    'billing.cfdi-invoices.preview-pdf',
                    // NO store/update/destroy (can't create/modify invoices)
                ])
                ->get();
            $customerRole->givePermissionTo($customerPermissions);
        }
    }
}

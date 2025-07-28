<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PurchaseAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();

        $permissions = [
            'suppliers.index',
            'suppliers.show',
            'suppliers.store',
            'suppliers.update',
            'suppliers.destroy',
            'purchase-orders.index',
            'purchase-orders.show',
            'purchase-orders.store',
            'purchase-orders.update',
            'purchase-orders.destroy',
            'purchase-order-items.index',
            'purchase-order-items.show',
            'purchase-order-items.store',
            'purchase-order-items.update',
            'purchase-order-items.destroy',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->where('guard_name', 'api')->first();
            
            if ($permission) {
                if ($godRole && !$godRole->hasPermissionTo($permission)) {
                    $godRole->givePermissionTo($permission);
                }
                
                if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                }
            }
        }

        Log::info('Asignando permisos de Purchase al rol god y admin, sin sobrescribir los existentes.');
    }
}

<?php

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SalesAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asignar permisos a roles
        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        $techRole = Role::where('name', 'tech')->where('guard_name', 'api')->first();

        if ($godRole) {
            // God tiene todos los permisos
            $allPermissions = Permission::where('guard_name', 'api')
                                      ->where(function($query) {
                                          $query->where('name', 'like', 'customers.%')
                                                ->orWhere('name', 'like', 'sales-orders.%')
                                                ->orWhere('name', 'like', 'sales-order-items.%');
                                      })
                                      ->get();
            $godRole->givePermissionTo($allPermissions);
        }

        if ($adminRole) {
            // Admin tiene permisos CRUD completos
            $adminPermissions = Permission::where('guard_name', 'api')
                                        ->whereIn('name', [
                                            'customers.index', 'customers.view', 'customers.show', 'customers.store', 
                                            'customers.update', 'customers.destroy',
                                            'sales-orders.index', 'sales-orders.view', 'sales-orders.show', 
                                            'sales-orders.store', 'sales-orders.update', 'sales-orders.destroy',
                                            'sales-order-items.index', 'sales-order-items.view', 'sales-order-items.show',
                                            'sales-order-items.store', 'sales-order-items.update', 'sales-order-items.destroy',
                                        ])->get();
            $adminRole->givePermissionTo($adminPermissions);
        }

        if ($techRole) {
            // Tech solo lectura
            $techPermissions = Permission::where('guard_name', 'api')
                                       ->whereIn('name', [
                                           'customers.index', 'customers.view', 'customers.show',
                                           'sales-orders.index', 'sales-orders.view', 'sales-orders.show',
                                           'sales-order-items.index', 'sales-order-items.view', 'sales-order-items.show',
                                       ])->get();
            $techRole->givePermissionTo($techPermissions);
        }
    }
}

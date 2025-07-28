<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;
use Modules\PermissionManager\Models\Role;
use Modules\PermissionManager\Models\Permission;

class PurchaseOrderItemPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos específicos para Purchase Order Items si no existen
        $permissions = [
            'purchase-order-items.index',
            'purchase-order-items.show',
            'purchase-order-items.store',
            'purchase-order-items.update',
            'purchase-order-items.destroy',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'api'
            ]);
        }

        // Obtener o crear roles con guard 'api'
        $godRole = Role::firstOrCreate([
            'name' => 'god',
            'guard_name' => 'api'
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin', 
            'guard_name' => 'api'
        ]);

        // Asignar permisos a los roles
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'api')
                ->first();
            
            if ($permission) {
                if (!$godRole->hasPermissionTo($permission)) {
                    $godRole->givePermissionTo($permission);
                }
                
                if (!$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                }
            }
        }

        // Asignar roles a usuarios específicos
        $godUsers = User::whereIn('email', ['system@audit.local', 'god@example.com'])->get();
        foreach ($godUsers as $user) {
            if (!$user->hasRole($godRole)) {
                $user->assignRole($godRole);
            }
        }

        $adminUsers = User::whereIn('email', ['admin@example.com'])->get();
        foreach ($adminUsers as $user) {
            if (!$user->hasRole($adminRole)) {
                $user->assignRole($adminRole);
            }
        }

        Log::info('PurchaseOrderItem permissions and role assignments completed successfully.');
    }
}

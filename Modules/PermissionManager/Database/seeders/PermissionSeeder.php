<?php

namespace Modules\PermissionManager\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\PermissionManager\Models\Permission;

class PermissionSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Crear usuario System (causer_id = 1) si no existe
        $system = User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'System',
                'email' => 'system@audit.local',
                'password' => 'system',
                'status' => 'active',
            ]
        );

        $permissions = [
            // Roles
            'roles.view',
            'roles.index',
            'roles.create',
            'roles.store',
            'roles.update',
            'roles.delete',
            'roles.destroy',
            // Permissions
            'permissions.view',
            'permissions.index',
            'permissions.create',
            'permissions.store',
            'permissions.update',
            'permissions.delete',
            'permissions.destroy',
            'permissions.assign',
            'permissions.revoke',
            // Users
            'users.view',
            'users.index',
            'users.create',
            'users.store',
            'users.update',
            'users.delete',
            // Own profile
            'profile.view',
            'profile.update',
            // Warehouses
            'warehouses.view',
            'warehouses.index',
            'warehouses.create',
            'warehouses.store',
            'warehouses.update',
            'warehouses.delete',
            'warehouses.destroy',
            // Warehouse Locations
            'warehouse-locations.view',
            'warehouse-locations.index',
            'warehouse-locations.create',
            'warehouse-locations.store',
            'warehouse-locations.update',
            'warehouse-locations.delete',
            'warehouse-locations.destroy',
            // Stock
            'stock.view',
            'stock.index',
            'stock.create',
            'stock.store',
            'stock.update',
            'stock.delete',
            'stock.destroy',
            // Product Batches
            'product-batches.view',
            'product-batches.index',
            'product-batches.create',
            'product-batches.store',
            'product-batches.update',
            'product-batches.delete',
            'product-batches.destroy',
        ];

        foreach ($permissions as $perm) {
            $created = Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api',
            ]);

            activity()
                ->causedBy($system)
                ->event('seeding')
                ->withProperties([
                    'attributes' => $created->only('id', 'name'),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'SeederScript'
                ])
                ->log("Permiso '{$perm}' creado o actualizado");

        }
    }
}

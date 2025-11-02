<?php

namespace Modules\PermissionManager\Database\Seeders;

use Modules\User\Models\User;
use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class PermissionSeeder extends Seeder
{
    use BulkPermissions;

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
            'roles.view', 'roles.index', 'roles.create', 'roles.store', 'roles.update', 'roles.delete', 'roles.destroy',
            // Permissions
            'permissions.view', 'permissions.index', 'permissions.create', 'permissions.store', 'permissions.update',
            'permissions.delete', 'permissions.destroy', 'permissions.assign', 'permissions.revoke',
            // Users
            'users.view', 'users.index', 'users.create', 'users.store', 'users.update', 'users.delete',
            // Own profile
            'profile.view', 'profile.update',
            // Warehouses
            'warehouses.view', 'warehouses.index', 'warehouses.create', 'warehouses.store', 'warehouses.update',
            'warehouses.delete', 'warehouses.destroy',
            // Warehouse Locations
            'warehouse-locations.view', 'warehouse-locations.index', 'warehouse-locations.create',
            'warehouse-locations.store', 'warehouse-locations.update', 'warehouse-locations.delete',
            'warehouse-locations.destroy',
            // Stock
            'stock.view', 'stock.index', 'stock.create', 'stock.store', 'stock.update', 'stock.delete', 'stock.destroy',
            // Product Batches
            'product-batches.view', 'product-batches.index', 'product-batches.create', 'product-batches.store',
            'product-batches.update', 'product-batches.delete', 'product-batches.destroy',
        ];

        // Use bulk insert - much faster than individual firstOrCreate
        $this->bulkCreatePermissions($permissions);

        // Log single activity for all permissions (instead of 62 individual logs)
        activity()
            ->causedBy($system)
            ->event('seeding')
            ->withProperties([
                'attributes' => ['count' => count($permissions)],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SeederScript'
            ])
            ->log('PermissionManager permissions bulk created');
    }
}

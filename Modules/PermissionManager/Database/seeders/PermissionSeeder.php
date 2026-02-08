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
            ['email' => 'system@audit.local'],
            [
                'name' => 'System',
                'password' => 'system',
                'status' => 'active',
            ]
        );

        $permissions = [
            // Roles
            'roles.index', 'roles.show', 'roles.store', 'roles.update', 'roles.destroy',
            // Permissions
            'permissions.index', 'permissions.show', 'permissions.store', 'permissions.update',
            'permissions.destroy', 'permissions.assign', 'permissions.revoke',
            // Users
            'users.index', 'users.show', 'users.store', 'users.update', 'users.destroy',
            // Own profile
            'profile.show', 'profile.update',
            // Warehouses
            'warehouses.index', 'warehouses.show', 'warehouses.store', 'warehouses.update',
            'warehouses.destroy',
            // Warehouse Locations
            'warehouse-locations.index', 'warehouse-locations.show', 'warehouse-locations.store',
            'warehouse-locations.update', 'warehouse-locations.destroy',
            // Stock
            'stock.index', 'stock.show', 'stock.store', 'stock.update', 'stock.destroy',
            // Product Batches
            'product-batches.index', 'product-batches.show', 'product-batches.store',
            'product-batches.update', 'product-batches.destroy',
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

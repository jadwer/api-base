<?php

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class SalesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Customer permissions
            'customers.index',
            'customers.view',
            'customers.show',
            'customers.store',
            'customers.update',
            'customers.destroy',
            
            // Sales Order permissions
            'sales-orders.index',
            'sales-orders.view', 
            'sales-orders.show',
            'sales-orders.store',
            'sales-orders.update',
            'sales-orders.destroy',
            
            // Sales Order Item permissions
            'sales-order-items.index',
            'sales-order-items.view',
            'sales-order-items.show',
            'sales-order-items.store',
            'sales-order-items.update',
            'sales-order-items.destroy',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        Log::info('Sales permissions created successfully.');
    }
}

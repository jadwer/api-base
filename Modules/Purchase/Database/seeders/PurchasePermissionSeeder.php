<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class PurchasePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Supplier permissions
            'suppliers.index',
            'suppliers.show',
            'suppliers.store',
            'suppliers.update',
            'suppliers.destroy',
            
            // Purchase Order permissions
            'purchase-orders.index',
            'purchase-orders.show',
            'purchase-orders.store',
            'purchase-orders.update',
            'purchase-orders.destroy',
            
            // Purchase Order Item permissions
            'purchase-order-items.index',
            'purchase-order-items.show',
            'purchase-order-items.store',
            'purchase-order-items.update',
            'purchase-order-items.destroy',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        Log::info('Purchase permissions created successfully.');
    }
}

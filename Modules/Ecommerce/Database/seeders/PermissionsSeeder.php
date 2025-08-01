<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding Ecommerce permissions...');
        
        // Create permissions
        Permission::firstOrCreate([
            'name' => 'ecommerce.shopping-carts.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.shopping-carts.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.shopping-carts.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.shopping-carts.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.shopping-carts.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.cart-items.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.cart-items.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.cart-items.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.cart-items.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.cart-items.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.coupons.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.coupons.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.coupons.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.coupons.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.coupons.destroy',
            'guard_name' => 'api',
        ]);
        
        // Assign permissions to roles

        // god role permissions
        $rolegod = Role::where('name', 'god')->where('guard_name', 'api')->first();
        if ($rolegod) {
            $rolegod->givePermissionTo('ecommerce.shopping-carts.index');
            $rolegod->givePermissionTo('ecommerce.shopping-carts.show');
            $rolegod->givePermissionTo('ecommerce.shopping-carts.store');
            $rolegod->givePermissionTo('ecommerce.shopping-carts.update');
            $rolegod->givePermissionTo('ecommerce.shopping-carts.destroy');
            $rolegod->givePermissionTo('ecommerce.cart-items.index');
            $rolegod->givePermissionTo('ecommerce.cart-items.show');
            $rolegod->givePermissionTo('ecommerce.cart-items.store');
            $rolegod->givePermissionTo('ecommerce.cart-items.update');
            $rolegod->givePermissionTo('ecommerce.cart-items.destroy');
            $rolegod->givePermissionTo('ecommerce.coupons.index');
            $rolegod->givePermissionTo('ecommerce.coupons.show');
            $rolegod->givePermissionTo('ecommerce.coupons.store');
            $rolegod->givePermissionTo('ecommerce.coupons.update');
            $rolegod->givePermissionTo('ecommerce.coupons.destroy');
        }

        // admin role permissions
        $roleadmin = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        if ($roleadmin) {
            $roleadmin->givePermissionTo('ecommerce.shopping-carts.index');
            $roleadmin->givePermissionTo('ecommerce.shopping-carts.show');
            $roleadmin->givePermissionTo('ecommerce.shopping-carts.store');
            $roleadmin->givePermissionTo('ecommerce.shopping-carts.update');
            $roleadmin->givePermissionTo('ecommerce.shopping-carts.destroy');
            $roleadmin->givePermissionTo('ecommerce.cart-items.index');
            $roleadmin->givePermissionTo('ecommerce.cart-items.show');
            $roleadmin->givePermissionTo('ecommerce.cart-items.store');
            $roleadmin->givePermissionTo('ecommerce.cart-items.update');
            $roleadmin->givePermissionTo('ecommerce.cart-items.destroy');
            $roleadmin->givePermissionTo('ecommerce.coupons.index');
            $roleadmin->givePermissionTo('ecommerce.coupons.show');
            $roleadmin->givePermissionTo('ecommerce.coupons.store');
            $roleadmin->givePermissionTo('ecommerce.coupons.update');
            $roleadmin->givePermissionTo('ecommerce.coupons.destroy');
        }

        // customer role permissions
        $rolecustomer = Role::where('name', 'customer')->where('guard_name', 'api')->first();
        if ($rolecustomer) {
            $rolecustomer->givePermissionTo('ecommerce.shopping-carts.index');
            $rolecustomer->givePermissionTo('ecommerce.shopping-carts.show');
            $rolecustomer->givePermissionTo('ecommerce.shopping-carts.store');
            $rolecustomer->givePermissionTo('ecommerce.shopping-carts.update');
            $rolecustomer->givePermissionTo('ecommerce.shopping-carts.destroy');
            $rolecustomer->givePermissionTo('ecommerce.cart-items.index');
            $rolecustomer->givePermissionTo('ecommerce.cart-items.show');
            $rolecustomer->givePermissionTo('ecommerce.cart-items.store');
            $rolecustomer->givePermissionTo('ecommerce.cart-items.update');
            $rolecustomer->givePermissionTo('ecommerce.cart-items.destroy');
            $rolecustomer->givePermissionTo('ecommerce.coupons.show');
        }

        // guest role permissions
        $roleguest = Role::where('name', 'guest')->where('guard_name', 'api')->first();
        if ($roleguest) {
            $roleguest->givePermissionTo('ecommerce.shopping-carts.store');
            $roleguest->givePermissionTo('ecommerce.shopping-carts.update');
            $roleguest->givePermissionTo('ecommerce.shopping-carts.show');
            $roleguest->givePermissionTo('ecommerce.cart-items.store');
            $roleguest->givePermissionTo('ecommerce.cart-items.update');
            $roleguest->givePermissionTo('ecommerce.cart-items.destroy');
        }

        // tech role permissions
        $roletech = Role::where('name', 'tech')->where('guard_name', 'api')->first();
        if ($roletech) {
            $roletech->givePermissionTo('ecommerce.shopping-carts.index');
            $roletech->givePermissionTo('ecommerce.shopping-carts.show');
            $roletech->givePermissionTo('ecommerce.cart-items.index');
            $roletech->givePermissionTo('ecommerce.cart-items.show');
            $roletech->givePermissionTo('ecommerce.coupons.index');
            $roletech->givePermissionTo('ecommerce.coupons.show');
        }
        
        $this->command->info('✅ Ecommerce permissions seeded successfully!');
    }
}

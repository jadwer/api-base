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

        // Phase 4.1 - Checkout Sessions
        Permission::firstOrCreate([
            'name' => 'ecommerce.checkout-sessions.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.checkout-sessions.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.checkout-sessions.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.checkout-sessions.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.checkout-sessions.destroy',
            'guard_name' => 'api',
        ]);

        // Phase 4.1 - Payment Transactions
        Permission::firstOrCreate([
            'name' => 'ecommerce.payment-transactions.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.payment-transactions.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.payment-transactions.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.payment-transactions.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.payment-transactions.destroy',
            'guard_name' => 'api',
        ]);

        // Phase 4.1 - Inventory Reservations
        Permission::firstOrCreate([
            'name' => 'ecommerce.inventory-reservations.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.inventory-reservations.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.inventory-reservations.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.inventory-reservations.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.inventory-reservations.destroy',
            'guard_name' => 'api',
        ]);

        // Phase 4.1 - Shipping Methods
        Permission::firstOrCreate([
            'name' => 'ecommerce.shipping-methods.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.shipping-methods.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.shipping-methods.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.shipping-methods.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.shipping-methods.destroy',
            'guard_name' => 'api',
        ]);

        // Phase 4.3.1 - Product Reviews
        Permission::firstOrCreate([
            'name' => 'ecommerce.product-reviews.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.product-reviews.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.product-reviews.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.product-reviews.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.product-reviews.destroy',
            'guard_name' => 'api',
        ]);

        // Phase 4.3.2 - Wishlists
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlists.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlists.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlists.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlists.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlists.destroy',
            'guard_name' => 'api',
        ]);

        // Phase 4.3.2 - Wishlist Items
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlist-items.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlist-items.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlist-items.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlist-items.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ecommerce.wishlist-items.destroy',
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
            // Phase 4.1 permissions
            $rolegod->givePermissionTo('ecommerce.checkout-sessions.index');
            $rolegod->givePermissionTo('ecommerce.checkout-sessions.show');
            $rolegod->givePermissionTo('ecommerce.checkout-sessions.store');
            $rolegod->givePermissionTo('ecommerce.checkout-sessions.update');
            $rolegod->givePermissionTo('ecommerce.checkout-sessions.destroy');
            $rolegod->givePermissionTo('ecommerce.payment-transactions.index');
            $rolegod->givePermissionTo('ecommerce.payment-transactions.show');
            $rolegod->givePermissionTo('ecommerce.payment-transactions.store');
            $rolegod->givePermissionTo('ecommerce.payment-transactions.update');
            $rolegod->givePermissionTo('ecommerce.payment-transactions.destroy');
            $rolegod->givePermissionTo('ecommerce.inventory-reservations.index');
            $rolegod->givePermissionTo('ecommerce.inventory-reservations.show');
            $rolegod->givePermissionTo('ecommerce.inventory-reservations.store');
            $rolegod->givePermissionTo('ecommerce.inventory-reservations.update');
            $rolegod->givePermissionTo('ecommerce.inventory-reservations.destroy');
            $rolegod->givePermissionTo('ecommerce.shipping-methods.index');
            $rolegod->givePermissionTo('ecommerce.shipping-methods.show');
            $rolegod->givePermissionTo('ecommerce.shipping-methods.store');
            $rolegod->givePermissionTo('ecommerce.shipping-methods.update');
            $rolegod->givePermissionTo('ecommerce.shipping-methods.destroy');
            // Phase 4.3.1 permissions
            $rolegod->givePermissionTo('ecommerce.product-reviews.index');
            $rolegod->givePermissionTo('ecommerce.product-reviews.show');
            $rolegod->givePermissionTo('ecommerce.product-reviews.store');
            $rolegod->givePermissionTo('ecommerce.product-reviews.update');
            $rolegod->givePermissionTo('ecommerce.product-reviews.destroy');
            // Phase 4.3.2 permissions
            $rolegod->givePermissionTo('ecommerce.wishlists.index');
            $rolegod->givePermissionTo('ecommerce.wishlists.show');
            $rolegod->givePermissionTo('ecommerce.wishlists.store');
            $rolegod->givePermissionTo('ecommerce.wishlists.update');
            $rolegod->givePermissionTo('ecommerce.wishlists.destroy');
            $rolegod->givePermissionTo('ecommerce.wishlist-items.index');
            $rolegod->givePermissionTo('ecommerce.wishlist-items.show');
            $rolegod->givePermissionTo('ecommerce.wishlist-items.store');
            $rolegod->givePermissionTo('ecommerce.wishlist-items.update');
            $rolegod->givePermissionTo('ecommerce.wishlist-items.destroy');
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
            // Phase 4.1 permissions
            $roleadmin->givePermissionTo('ecommerce.checkout-sessions.index');
            $roleadmin->givePermissionTo('ecommerce.checkout-sessions.show');
            $roleadmin->givePermissionTo('ecommerce.checkout-sessions.store');
            $roleadmin->givePermissionTo('ecommerce.checkout-sessions.update');
            $roleadmin->givePermissionTo('ecommerce.checkout-sessions.destroy');
            $roleadmin->givePermissionTo('ecommerce.payment-transactions.index');
            $roleadmin->givePermissionTo('ecommerce.payment-transactions.show');
            $roleadmin->givePermissionTo('ecommerce.payment-transactions.store');
            $roleadmin->givePermissionTo('ecommerce.payment-transactions.update');
            $roleadmin->givePermissionTo('ecommerce.payment-transactions.destroy');
            $roleadmin->givePermissionTo('ecommerce.inventory-reservations.index');
            $roleadmin->givePermissionTo('ecommerce.inventory-reservations.show');
            $roleadmin->givePermissionTo('ecommerce.inventory-reservations.store');
            $roleadmin->givePermissionTo('ecommerce.inventory-reservations.update');
            $roleadmin->givePermissionTo('ecommerce.inventory-reservations.destroy');
            $roleadmin->givePermissionTo('ecommerce.shipping-methods.index');
            $roleadmin->givePermissionTo('ecommerce.shipping-methods.show');
            $roleadmin->givePermissionTo('ecommerce.shipping-methods.store');
            $roleadmin->givePermissionTo('ecommerce.shipping-methods.update');
            $roleadmin->givePermissionTo('ecommerce.shipping-methods.destroy');
            // Phase 4.3.1 permissions
            $roleadmin->givePermissionTo('ecommerce.product-reviews.index');
            $roleadmin->givePermissionTo('ecommerce.product-reviews.show');
            $roleadmin->givePermissionTo('ecommerce.product-reviews.store');
            $roleadmin->givePermissionTo('ecommerce.product-reviews.update');
            $roleadmin->givePermissionTo('ecommerce.product-reviews.destroy');
            // Phase 4.3.2 permissions
            $roleadmin->givePermissionTo('ecommerce.wishlists.index');
            $roleadmin->givePermissionTo('ecommerce.wishlists.show');
            $roleadmin->givePermissionTo('ecommerce.wishlists.store');
            $roleadmin->givePermissionTo('ecommerce.wishlists.update');
            $roleadmin->givePermissionTo('ecommerce.wishlists.destroy');
            $roleadmin->givePermissionTo('ecommerce.wishlist-items.index');
            $roleadmin->givePermissionTo('ecommerce.wishlist-items.show');
            $roleadmin->givePermissionTo('ecommerce.wishlist-items.store');
            $roleadmin->givePermissionTo('ecommerce.wishlist-items.update');
            $roleadmin->givePermissionTo('ecommerce.wishlist-items.destroy');
        }

        // customer role permissions (can manage their own carts/items but not list all or manage others')
        $rolecustomer = Role::where('name', 'customer')->where('guard_name', 'api')->first();
        if ($rolecustomer) {
            $rolecustomer->givePermissionTo('ecommerce.shopping-carts.store');
            $rolecustomer->givePermissionTo('ecommerce.cart-items.store');
            $rolecustomer->givePermissionTo('ecommerce.coupons.show');
            // Phase 4.1 permissions (can manage their own checkout sessions)
            $rolecustomer->givePermissionTo('ecommerce.checkout-sessions.show');
            $rolecustomer->givePermissionTo('ecommerce.checkout-sessions.store');
            $rolecustomer->givePermissionTo('ecommerce.checkout-sessions.update');
            $rolecustomer->givePermissionTo('ecommerce.payment-transactions.show');
            $rolecustomer->givePermissionTo('ecommerce.shipping-methods.index');
            $rolecustomer->givePermissionTo('ecommerce.shipping-methods.show');
            // Phase 4.3.1 permissions (customers can create reviews)
            $rolecustomer->givePermissionTo('ecommerce.product-reviews.store');
            // Phase 4.3.2 permissions (customers can manage their own wishlists)
            $rolecustomer->givePermissionTo('ecommerce.wishlists.store');
            $rolecustomer->givePermissionTo('ecommerce.wishlist-items.store');
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
            // Phase 4.1 permissions (read-only)
            $roletech->givePermissionTo('ecommerce.checkout-sessions.index');
            $roletech->givePermissionTo('ecommerce.checkout-sessions.show');
            $roletech->givePermissionTo('ecommerce.payment-transactions.index');
            $roletech->givePermissionTo('ecommerce.payment-transactions.show');
            $roletech->givePermissionTo('ecommerce.inventory-reservations.index');
            $roletech->givePermissionTo('ecommerce.inventory-reservations.show');
            $roletech->givePermissionTo('ecommerce.shipping-methods.index');
            $roletech->givePermissionTo('ecommerce.shipping-methods.show');
            // Phase 4.3.1 permissions (can moderate reviews)
            $roletech->givePermissionTo('ecommerce.product-reviews.index');
            $roletech->givePermissionTo('ecommerce.product-reviews.show');
            $roletech->givePermissionTo('ecommerce.product-reviews.destroy');
            // Phase 4.3.2 permissions (read-only)
            $roletech->givePermissionTo('ecommerce.wishlists.index');
            $roletech->givePermissionTo('ecommerce.wishlists.show');
            $roletech->givePermissionTo('ecommerce.wishlist-items.index');
            $roletech->givePermissionTo('ecommerce.wishlist-items.show');
        }

        $this->command->info('✅ Ecommerce permissions seeded successfully!');
    }
}

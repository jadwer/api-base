<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EcommerceAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        $techRole = Role::where('name', 'tech')->where('guard_name', 'api')->first();
        $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();

        // God gets all ecommerce permissions
        if ($godRole) {
            $allPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'ecommerce.%')
                ->get();
            $godRole->givePermissionTo($allPermissions);
        }

        // Admin gets all ecommerce permissions
        if ($adminRole) {
            $adminPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'ecommerce.%')
                ->get();
            $adminRole->givePermissionTo($adminPermissions);
        }

        // Tech gets read-only permissions (index, show only)
        if ($techRole) {
            $techPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'ecommerce.%')
                ->where(function ($query) {
                    $query->where('name', 'like', '%.index')
                          ->orWhere('name', 'like', '%.show');
                })
                ->get();
            $techRole->givePermissionTo($techPermissions);
        }

        // Customer gets limited permissions
        // Ownership-based resources: customer gets index+store only
        // The authorizer handles show/update/destroy via ownership checks
        if ($customerRole) {
            $customerPermissions = Permission::where('guard_name', 'api')
                ->whereIn('name', [
                    // Shopping Carts - ownership handles show/update/destroy
                    'ecommerce.shopping-carts.index',
                    'ecommerce.shopping-carts.store',
                    // Cart Items - ownership via parent cart handles show/update/destroy
                    'ecommerce.cart-items.index',
                    'ecommerce.cart-items.store',
                    // Coupons - read only (no ownership)
                    'ecommerce.coupons.index',
                    'ecommerce.coupons.show',
                    // Checkout - store only, ownership handles update
                    'ecommerce.checkout-sessions.store',
                    // Payments - index only, ownership handles show
                    'ecommerce.payment-transactions.index',
                    // Shipping - read only (no ownership)
                    'ecommerce.shipping-methods.index',
                    'ecommerce.shipping-methods.show',
                    // Reviews - ownership+status handles show/update/destroy
                    'ecommerce.product-reviews.index',
                    'ecommerce.product-reviews.store',
                    // Wishlists - ownership+is_public handles show/update/destroy
                    'ecommerce.wishlists.index',
                    'ecommerce.wishlists.store',
                    'ecommerce.wishlist-items.index',
                    'ecommerce.wishlist-items.store',
                    // Comparisons - ownership+is_public handles show/update/destroy
                    'ecommerce.product-comparisons.index',
                    'ecommerce.product-comparisons.store',
                    'ecommerce.product-comparison-items.index',
                    // Questions - ownership+status handles show/update/destroy
                    'ecommerce.product-questions.index',
                    'ecommerce.product-questions.store',
                    // Answers - read only
                    'ecommerce.product-answers.index',
                    'ecommerce.product-answers.show',
                ])->get();
            $customerRole->givePermissionTo($customerPermissions);
        }
    }
}

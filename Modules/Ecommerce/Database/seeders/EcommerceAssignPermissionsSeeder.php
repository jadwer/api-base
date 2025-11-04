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

        // Tech gets all ecommerce permissions (same as admin)
        if ($techRole) {
            $techPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'ecommerce.%')
                ->get();
            $techRole->givePermissionTo($techPermissions);
        }

        // Customer gets limited permissions - handled by Authorizer for ownership checks
        if ($customerRole) {
            $customerPermissions = Permission::where('guard_name', 'api')
                ->whereIn('name', [
                    // Shopping Carts - NO index (can't list all carts), but can manage their own via Authorizer
                    'ecommerce.shopping-carts.show',
                    'ecommerce.shopping-carts.store',
                    'ecommerce.shopping-carts.update',
                    'ecommerce.shopping-carts.destroy',
                    // Cart Items - NO index (can't list all items), but can manage their own via Authorizer
                    'ecommerce.cart-items.show',
                    'ecommerce.cart-items.store',
                    'ecommerce.cart-items.update',
                    'ecommerce.cart-items.destroy',
                    // Coupons - read only
                    'ecommerce.coupons.index',
                    'ecommerce.coupons.show',
                    // Checkout - full access for their own checkouts
                    'ecommerce.checkout-sessions.index',
                    'ecommerce.checkout-sessions.show',
                    'ecommerce.checkout-sessions.store',
                    'ecommerce.checkout-sessions.update',
                    // Payments - read only for their own payments
                    'ecommerce.payment-transactions.index',
                    'ecommerce.payment-transactions.show',
                    // Shipping - read only
                    'ecommerce.shipping-methods.index',
                    'ecommerce.shipping-methods.show',
                    // Reviews - full CRUD for their own reviews
                    'ecommerce.product-reviews.index',
                    'ecommerce.product-reviews.show',
                    'ecommerce.product-reviews.store',
                    'ecommerce.product-reviews.update',
                    'ecommerce.product-reviews.destroy',
                    // Wishlists - full CRUD for their own wishlists
                    'ecommerce.wishlists.index',
                    'ecommerce.wishlists.show',
                    'ecommerce.wishlists.store',
                    'ecommerce.wishlists.update',
                    'ecommerce.wishlists.destroy',
                    'ecommerce.wishlist-items.index',
                    'ecommerce.wishlist-items.show',
                    'ecommerce.wishlist-items.store',
                    'ecommerce.wishlist-items.update',
                    'ecommerce.wishlist-items.destroy',
                    // Product Comparisons - full CRUD for their own comparisons
                    'ecommerce.product-comparisons.index',
                    'ecommerce.product-comparisons.show',
                    'ecommerce.product-comparisons.store',
                    'ecommerce.product-comparisons.update',
                    'ecommerce.product-comparisons.destroy',
                    'ecommerce.product-comparison-items.index',
                    'ecommerce.product-comparison-items.show',
                    'ecommerce.product-comparison-items.store',
                    'ecommerce.product-comparison-items.update',
                    'ecommerce.product-comparison-items.destroy',
                    // Product Questions - full CRUD for their own questions
                    'ecommerce.product-questions.index',
                    'ecommerce.product-questions.show',
                    'ecommerce.product-questions.store',
                    'ecommerce.product-questions.update',
                    'ecommerce.product-questions.destroy',
                    // Product Answers - read only (can't create/update/delete answers)
                    'ecommerce.product-answers.index',
                    'ecommerce.product-answers.show',
                ])->get();
            $customerRole->givePermissionTo($customerPermissions);
        }
    }
}

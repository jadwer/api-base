<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class PermissionsSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding permissions...');

        $permissions = [
            'ecommerce.shopping-carts.index',
            'ecommerce.shopping-carts.show',
            'ecommerce.shopping-carts.store',
            'ecommerce.shopping-carts.update',
            'ecommerce.shopping-carts.destroy',
            'ecommerce.cart-items.index',
            'ecommerce.cart-items.show',
            'ecommerce.cart-items.store',
            'ecommerce.cart-items.update',
            'ecommerce.cart-items.destroy',
            'ecommerce.coupons.index',
            'ecommerce.coupons.show',
            'ecommerce.coupons.store',
            'ecommerce.coupons.update',
            'ecommerce.coupons.destroy',
            'ecommerce.checkout-sessions.index',
            'ecommerce.checkout-sessions.show',
            'ecommerce.checkout-sessions.store',
            'ecommerce.checkout-sessions.update',
            'ecommerce.checkout-sessions.destroy',
            'ecommerce.payment-transactions.index',
            'ecommerce.payment-transactions.show',
            'ecommerce.payment-transactions.store',
            'ecommerce.payment-transactions.update',
            'ecommerce.payment-transactions.destroy',
            'ecommerce.inventory-reservations.index',
            'ecommerce.inventory-reservations.show',
            'ecommerce.inventory-reservations.store',
            'ecommerce.inventory-reservations.update',
            'ecommerce.inventory-reservations.destroy',
            'ecommerce.shipping-methods.index',
            'ecommerce.shipping-methods.show',
            'ecommerce.shipping-methods.store',
            'ecommerce.shipping-methods.update',
            'ecommerce.shipping-methods.destroy',
            'ecommerce.product-reviews.index',
            'ecommerce.product-reviews.show',
            'ecommerce.product-reviews.store',
            'ecommerce.product-reviews.update',
            'ecommerce.product-reviews.destroy',
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
            'ecommerce.product-questions.index',
            'ecommerce.product-questions.show',
            'ecommerce.product-questions.store',
            'ecommerce.product-questions.update',
            'ecommerce.product-questions.destroy',
            'ecommerce.product-answers.index',
            'ecommerce.product-answers.show',
            'ecommerce.product-answers.store',
            'ecommerce.product-answers.update',
            'ecommerce.product-answers.destroy',
        ];

        $this->bulkCreatePermissions($permissions);

        $this->command->info('✅ Permissions seeded successfully!');
    }
}

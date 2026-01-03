<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\CartItem;

class CartItemShowTest extends TestCase
{



    public function test_admin_can_view_CartItem(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get("/api/v1/cart-items/{$cartItem->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'shoppingCartId',
                        'productId',
                        'quantity',
                        'unitPrice',
                        'originalPrice',
                        'discountPercent',
                        'discountAmount',
                        'subtotal',
                        'taxRate',
                        'taxAmount',
                        'total',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_CartItem_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $cartItem = CartItem::factory()->create(['quantity' => 99.99, 'unit_price' => 99.99, 'original_price' => 99.99, 'discount_percent' => 99.99, 'discount_amount' => 99.99, 'subtotal' => 99.99, 'tax_rate' => 99.99, 'tax_amount' => 99.99, 'total' => 99.99]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get("/api/v1/cart-items/{$cartItem->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'shoppingCartId',
                        'productId',
                        'quantity',
                        'unitPrice',
                        'originalPrice',
                        'discountPercent',
                        'discountAmount',
                        'subtotal',
                        'taxRate',
                        'taxAmount',
                        'total',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_CartItem_with_permission(): void
    {
        $tech = $this->getTechUser();
        $cartItem = CartItem::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get("/api/v1/cart-items/{$cartItem->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_CartItem(): void
    {
        $customer = $this->getCustomerUser();
        // Create a cart that belongs to a DIFFERENT user (not the customer)
        $otherUser = User::factory()->create();
        $shoppingCart = \Modules\Ecommerce\Models\ShoppingCart::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        $cartItem = CartItem::factory()->create([
            'shopping_cart_id' => $shoppingCart->id,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get("/api/v1/cart-items/{$cartItem->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_CartItem(): void
    {
        $cartItem = CartItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cart-items')
            ->get("/api/v1/cart-items/{$cartItem->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_CartItem(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get('/api/v1/cart-items/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get("/api/v1/cart-items/{$cartItem->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

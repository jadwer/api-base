<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\CartItem;

class CartItemShowTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

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
        $cartItem = CartItem::factory()->create();

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

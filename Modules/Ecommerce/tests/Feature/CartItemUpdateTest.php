<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\CartItem;

class CartItemUpdateTest extends TestCase
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

    public function test_admin_can_update_CartItem(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        $data = [
            'type' => 'cart-items',
            'id' => (string) $cartItem->id,
            'attributes' => [
                'quantity' => 5.0,
                'unitPrice' => 25.99,
                'status' => 'inactive'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{$cartItem->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5.0,
            'unit_price' => 25.99,
            'status' => 'inactive'
        ]);
    }

    public function test_admin_can_partially_update_CartItem(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create([
            'quantity' => 2.0,
            'metadata' => ['note' => 'Original Note']
        ]);

        $data = [
            'type' => 'cart-items',
            'id' => (string) $cartItem->id,
            'attributes' => [
                'quantity' => 5.0
                // metadata should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{$cartItem->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5.0
        ]);
        
        $cartItem->refresh();
        $this->assertEquals(['note' => 'Original Note'], $cartItem->metadata);
    }

    public function test_admin_can_update_CartItem_metadata(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'cart-items',
            'id' => (string) $cartItem->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{$cartItem->id}");

        $response->assertOk();
        
        $cartItem->refresh();
        $this->assertEquals($metadata, $cartItem->metadata);
    }

    public function test_customer_user_cannot_update_CartItem(): void
    {
        $customer = $this->getCustomerUser();
        $cartItem = CartItem::factory()->create();

        $data = [
            'type' => 'cart-items',
            'id' => (string) $cartItem->id,
            'attributes' => [
                'quantity' => 10.0
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{$cartItem->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_CartItem(): void
    {
        $cartItem = CartItem::factory()->create();

        $data = [
            'type' => 'cart-items',
            'id' => (string) $cartItem->id,
            'attributes' => [
                'status' => 'inactive'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{$cartItem->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_CartItem(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cart-items',
            'id' => '999999',
            'attributes' => [
                'quantity' => 5.0
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch('/api/v1/cart-items/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_CartItem_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        $data = [
            'type' => 'cart-items',
            'id' => (string) $cartItem->id,
            'attributes' => [
                'quantity' => -5.0, // Negative quantity
                'status' => 'invalid_status' // Invalid status
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{$cartItem->id}");

        $response->assertStatus(422);
    }
}

<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;

class ShoppingCartUpdateTest extends TestCase
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

    public function test_admin_can_update_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'status' => 'inactive',
                'totalAmount' => 150.00,
                'currency' => 'EUR',
                'couponCode' => 'UPDATED123',
                'discountAmount' => 15.00,
                'taxAmount' => 12.00,
                'shippingAmount' => 8.00,
                'notes' => 'Updated shopping cart'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $shoppingCart->id,
            'status' => 'inactive',
            'total_amount' => 150.00,
            'currency' => 'EUR',
            'coupon_code' => 'UPDATED123',
            'discount_amount' => 15.00,
            'notes' => 'Updated shopping cart'
        ]);
    }

    public function test_admin_can_partially_update_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create([
            'status' => 'active',
            'total_amount' => 100.00,
            'currency' => 'USD',
            'notes' => 'Original Notes'
        ]);

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'totalAmount' => 200.00,
                'discountAmount' => 0.00,
                'taxAmount' => 0.00,
                'shippingAmount' => 0.00
                // other fields should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $shoppingCart->id,
            'total_amount' => 200.00,
            'status' => 'active',
            'currency' => 'USD',
            'notes' => 'Original Notes'
        ]);
    }

    public function test_admin_can_update_ShoppingCart_metadata(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'metadata' => $metadata,
                'totalAmount' => (float)$shoppingCart->total_amount,
                'discountAmount' => 0.00,
                'taxAmount' => 0.00,
                'shippingAmount' => 0.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        
        $shoppingCart->refresh();
        $this->assertEquals($metadata, $shoppingCart->metadata);
    }

    public function test_customer_user_cannot_update_ShoppingCart(): void
    {
        $customer = $this->getCustomerUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'status' => 'inactive',
                'totalAmount' => 75.00
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ShoppingCart(): void
    {
        $shoppingCart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'status' => 'expired',
                'totalAmount' => 30.00
            ]
        ];

        $response = $this->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shopping-carts',
            'id' => '999999',
            'attributes' => [
                'status' => 'active',
                'totalAmount' => 99.99
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch('/api/v1/shopping-carts/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ShoppingCart_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'status' => 'invalid_status', // Invalid status
                'totalAmount' => -50.00, // Negative amount
                'currency' => 'INVALID' // Invalid currency format
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertStatus(422);
    }
}

<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\CartItem;

class CartItemStoreTest extends TestCase
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

    public function test_admin_can_create_CartItem(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'quantity' => 99.99,
                'unitPrice' => 99.99,
                'originalPrice' => 99.99,
                'discountPercent' => 99.99,
                'discountAmount' => 99.99,
                'subtotal' => 99.99,
                'taxRate' => 99.99,
                'taxAmount' => 99.99,
                'total' => 99.99
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertCreated();
        
        $this->assertDatabaseHas('cart_items', ['quantity' => 99.99, 'unit_price' => 99.99, 'original_price' => 99.99, 'discount_percent' => 99.99, 'discount_amount' => 99.99, 'subtotal' => 99.99, 'tax_rate' => 99.99, 'tax_amount' => 99.99, 'total' => 99.99]);
    }

    public function test_admin_can_create_CartItem_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cart-items',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_CartItem(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'name' => 'Unauthorized CartItem',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_CartItem(): void
    {
        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'name' => 'Guest CartItem',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertStatus(401);
    }

    public function test_cannot_create_CartItem_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_CartItem_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertStatus(422);
    }
}

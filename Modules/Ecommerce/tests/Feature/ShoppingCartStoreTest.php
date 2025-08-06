<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;

class ShoppingCartStoreTest extends TestCase
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

    public function test_admin_can_create_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'status' => 'active',
                'expiresAt' => '2024-12-31',
                'totalAmount' => 99.99,
                'currency' => 'USD',
                'couponCode' => 'TEST123',
                'discountAmount' => 10.00,
                'taxAmount' => 8.99,
                'shippingAmount' => 5.99,
                'notes' => 'Test shopping cart'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('shopping_carts', ['status' => 'active', 'total_amount' => 99.99, 'currency' => 'USD', 'coupon_code' => 'TEST123', 'discount_amount' => 10.00, 'tax_amount' => 8.99, 'shipping_amount' => 5.99, 'notes' => 'Test shopping cart']);
    }

    public function test_admin_can_create_ShoppingCart_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'status' => 'active',
                'totalAmount' => 0.00,
                'currency' => 'USD'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertCreated();
    }

    public function test_customer_user_can_create_ShoppingCart(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'status' => 'active',
                'totalAmount' => 50.00,
                'currency' => 'USD'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('shopping_carts', [
            'status' => 'active',
            'total_amount' => 50.00,
            'currency' => 'USD'
        ]);
    }

    public function test_guest_cannot_create_ShoppingCart_without_auth(): void
    {
        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'status' => 'active',
                'totalAmount' => 25.00,
                'currency' => 'USD'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ShoppingCart_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'notes' => 'Missing required fields'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/status', '/data/attributes/totalAmount', '/data/attributes/currency'], $response);
    }

    public function test_cannot_create_ShoppingCart_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'status' => 'invalid_status', // Invalid status
                'totalAmount' => -10.00, // Negative amount
                'currency' => 'TOOLONG' // Invalid currency format
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertStatus(422);
    }
}

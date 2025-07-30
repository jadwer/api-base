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
                'expiresAt' => '2024-01-01',
                'totalAmount' => 99.99,
                'currency' => 'test string',
                'couponCode' => 'TEST123',
                'discountAmount' => 99.99,
                'taxAmount' => 99.99,
                'shippingAmount' => 99.99,
                'notes' => 'test description'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertCreated();
        
        $this->assertDatabaseHas('shopping_carts', ['status' => 'active', 'expires_at' => 'test value', 'total_amount' => 99.99, 'currency' => 'test string', 'coupon_code' => 'TEST123', 'discount_amount' => 99.99, 'tax_amount' => 99.99, 'shipping_amount' => 99.99, 'notes' => 'test description']);
    }

    public function test_admin_can_create_ShoppingCart_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shopping-carts',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ShoppingCart(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'name' => 'Unauthorized ShoppingCart',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ShoppingCart(): void
    {
        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'name' => 'Guest ShoppingCart',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->post('/api/v1/shopping-carts');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_ShoppingCart_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shopping-carts',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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

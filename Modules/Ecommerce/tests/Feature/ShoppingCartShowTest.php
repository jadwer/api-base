<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;

class ShoppingCartShowTest extends TestCase
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

    public function test_admin_can_view_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'sessionId',
                        'userId',
                        'status',
                        'expiresAt',
                        'totalAmount',
                        'currency',
                        'couponCode',
                        'discountAmount',
                        'taxAmount',
                        'shippingAmount',
                        'notes',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_ShoppingCart_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $shoppingCart = ShoppingCart::factory()->create(['status' => 'active', 'expires_at' => now(), 'total_amount' => 99.99, 'currency' => 'test string', 'coupon_code' => 'TEST123', 'discount_amount' => 99.99, 'tax_amount' => 99.99, 'shipping_amount' => 99.99, 'notes' => 'test description']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'sessionId',
                        'userId',
                        'status',
                        'expiresAt',
                        'totalAmount',
                        'currency',
                        'couponCode',
                        'discountAmount',
                        'taxAmount',
                        'shippingAmount',
                        'notes',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_ShoppingCart_with_permission(): void
    {
        $tech = $this->getTechUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ShoppingCart(): void
    {
        $customer = $this->getCustomerUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ShoppingCart(): void
    {
        $shoppingCart = ShoppingCart::factory()->create();

        $response = $this->jsonApi()
            ->expects('shopping-carts')
            ->get("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get('/api/v1/shopping-carts/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

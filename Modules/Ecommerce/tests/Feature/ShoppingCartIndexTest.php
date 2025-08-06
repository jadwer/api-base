<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;

class ShoppingCartIndexTest extends TestCase
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

    public function test_admin_can_list_ShoppingCarts(): void
    {
        $admin = $this->getAdminUser();
        
        ShoppingCart::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get('/api/v1/shopping-carts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ShoppingCarts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ShoppingCart::factory()->create(['status' => 'active']);
        ShoppingCart::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get('/api/v1/shopping-carts?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_ShoppingCarts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ShoppingCart::factory()->create(['status' => 'active']);
        ShoppingCart::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get('/api/v1/shopping-carts?filter[status]=active');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ShoppingCarts_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get('/api/v1/shopping-carts');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ShoppingCarts(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get('/api/v1/shopping-carts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ShoppingCarts(): void
    {
        $response = $this->jsonApi()
            ->expects('shopping-carts')
            ->get('/api/v1/shopping-carts');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ShoppingCarts(): void
    {
        $admin = $this->getAdminUser();
        
        ShoppingCart::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->get('/api/v1/shopping-carts?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

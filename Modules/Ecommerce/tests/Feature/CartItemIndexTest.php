<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\CartItem;

class CartItemIndexTest extends TestCase
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

    public function test_admin_can_list_CartItems(): void
    {
        $admin = $this->getAdminUser();
        
        CartItem::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get('/api/v1/cart-items');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_CartItems_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        CartItem::factory()->create(['name' => 'B Item']);
        CartItem::factory()->create(['name' => 'A Item']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get('/api/v1/cart-items?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertEquals(['A Item', 'B Item'], $names->toArray());
    }

    public function test_admin_can_filter_CartItems_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        CartItem::factory()->create(['is_active' => true]);
        CartItem::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get('/api/v1/cart-items?filter[is_active]=true');

        $response->assertOk();
        $statuses = collect($response->json('data'))->pluck('attributes.is_active')->unique();
        $this->assertEquals([true], $statuses->toArray());
    }

    public function test_tech_user_can_list_CartItems_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get('/api/v1/cart-items');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_CartItems(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get('/api/v1/cart-items');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_CartItems(): void
    {
        $response = $this->jsonApi()
            ->expects('cart-items')
            ->get('/api/v1/cart-items');

        $response->assertStatus(401);
    }

    public function test_can_paginate_CartItems(): void
    {
        $admin = $this->getAdminUser();
        
        CartItem::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->get('/api/v1/cart-items?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

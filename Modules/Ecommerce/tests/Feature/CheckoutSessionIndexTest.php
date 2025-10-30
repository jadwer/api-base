<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\ShoppingCart;

class CheckoutSessionIndexTest extends TestCase
{
    public function test_admin_can_list_checkout_sessions(): void
    {
        $admin = $this->getAdminUser();

        CheckoutSession::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'shoppingCartId',
                        'userId',
                        'status',
                        'step',
                        'totalAmount',
                        'currency',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_checkout_sessions_by_status(): void
    {
        $admin = $this->getAdminUser();

        CheckoutSession::factory()->create(['status' => 'initiated']);
        CheckoutSession::factory()->create(['status' => 'completed']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_checkout_sessions_by_status(): void
    {
        $admin = $this->getAdminUser();

        CheckoutSession::factory()->create(['status' => 'initiated']);
        CheckoutSession::factory()->create(['status' => 'completed']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions?filter[status]=initiated');

        $response->assertOk();
    }

    public function test_tech_user_can_list_checkout_sessions_with_permission(): void
    {
        $tech = $this->getTechUser();

        CheckoutSession::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_all_checkout_sessions(): void
    {
        $customer = $this->getCustomerUser();

        CheckoutSession::factory()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_checkout_sessions(): void
    {
        CheckoutSession::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions');

        $response->assertStatus(401);
    }

    public function test_can_paginate_checkout_sessions(): void
    {
        $admin = $this->getAdminUser();

        CheckoutSession::factory()->count(15)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->get('/api/v1/checkout-sessions?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
    }
}

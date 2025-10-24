<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentMethod;

class PaymentMethodIndexTest extends TestCase
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

    public function test_admin_can_list_PaymentMethods(): void
    {
        $admin = $this->getAdminUser();
        
        PaymentMethod::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get('/api/v1/payment-methods');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'code',
                        'name',
                        'type',
                        'requiresReference',
                        'isActive',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_PaymentMethods_by_name(): void
    {
        $admin = $this->getAdminUser();
        
        PaymentMethod::factory()->create(['name' => 'Test Name']);
        PaymentMethod::factory()->create(['name' => 'Test Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get('/api/v1/payment-methods?sort=name');

        $response->assertOk();
    }

    public function test_admin_can_filter_PaymentMethods_by_type(): void
    {
        $admin = $this->getAdminUser();
        
        PaymentMethod::factory()->create(['type' => 'test string']);
        PaymentMethod::factory()->create(['type' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get('/api/v1/payment-methods?filter[type]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_PaymentMethods_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get('/api/v1/payment-methods');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_PaymentMethods(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get('/api/v1/payment-methods');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_PaymentMethods(): void
    {
        $response = $this->jsonApi()
            ->expects('payment-methods')
            ->get('/api/v1/payment-methods');

        $response->assertStatus(401);
    }

    public function test_can_paginate_PaymentMethods(): void
    {
        $admin = $this->getAdminUser();
        
        PaymentMethod::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get('/api/v1/payment-methods?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

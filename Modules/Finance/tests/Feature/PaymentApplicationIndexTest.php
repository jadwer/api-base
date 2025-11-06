<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationIndexTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_list_PaymentApplications(): void
    {
        $admin = $this->getAdminUser();
        
        PaymentApplication::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get('/api/v1/payment-applications');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'paymentId',
                        'arInvoiceId',
                        'amount',
                        'applicationDate',
                        'notes',
                        'isActive',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_PaymentApplications_by_createdAt(): void
    {
        $admin = $this->getAdminUser();
        
        PaymentApplication::factory()->create([]);
        PaymentApplication::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get('/api/v1/payment-applications?sort=createdAt');

        $response->assertOk();
    }

    public function test_admin_can_filter_PaymentApplications_by_isActive(): void
    {
        $admin = $this->getAdminUser();
        
        PaymentApplication::factory()->create(['isActive' => true]);
        PaymentApplication::factory()->create(['isActive' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get('/api/v1/payment-applications?filter[isActive]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_PaymentApplications_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get('/api/v1/payment-applications');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_PaymentApplications(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get('/api/v1/payment-applications');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_PaymentApplications(): void
    {
        $response = $this->jsonApi()
            ->expects('payment-applications')
            ->get('/api/v1/payment-applications');

        $response->assertStatus(401);
    }

    public function test_can_paginate_PaymentApplications(): void
    {
        $admin = $this->getAdminUser();
        
        PaymentApplication::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get('/api/v1/payment-applications?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

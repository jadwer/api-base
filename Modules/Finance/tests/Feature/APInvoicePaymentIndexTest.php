<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoicePayment;

class APInvoicePaymentIndexTest extends TestCase
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

    public function test_admin_can_list_APInvoicePayments(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoicePayment::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get('/api/v1/a-p-invoice-payments');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'apInvoiceId',
                        'apPaymentId',
                        'amountApplied',
                        'appliedAt',
                        'exchangeRateAtApply',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_APInvoicePayments_by_createdAt(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoicePayment::factory()->create([]);
        APInvoicePayment::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get('/api/v1/a-p-invoice-payments?sort=createdAt');

        $response->assertOk();
    }

    public function test_admin_can_filter_APInvoicePayments_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoicePayment::factory()->create([]);
        APInvoicePayment::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get('/api/v1/a-p-invoice-payments?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_APInvoicePayments_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get('/api/v1/a-p-invoice-payments');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_APInvoicePayments(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get('/api/v1/a-p-invoice-payments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_APInvoicePayments(): void
    {
        $response = $this->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get('/api/v1/a-p-invoice-payments');

        $response->assertStatus(401);
    }

    public function test_can_paginate_APInvoicePayments(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoicePayment::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get('/api/v1/a-p-invoice-payments?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

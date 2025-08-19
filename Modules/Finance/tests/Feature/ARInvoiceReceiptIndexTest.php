<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoiceReceipt;

class ARInvoiceReceiptIndexTest extends TestCase
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

    public function test_admin_can_list_ARInvoiceReceipts(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoiceReceipt::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->get('/api/v1/a-r-invoice-receipts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'arInvoiceId',
                        'arReceiptId',
                        'amountApplied',
                        'appliedAt',
                        'exchangeRateAtApply',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ARInvoiceReceipts_by_createdAt(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoiceReceipt::factory()->create([]);
        ARInvoiceReceipt::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->get('/api/v1/a-r-invoice-receipts?sort=createdAt');

        $response->assertOk();
    }

    public function test_admin_can_filter_ARInvoiceReceipts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoiceReceipt::factory()->create([]);
        ARInvoiceReceipt::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->get('/api/v1/a-r-invoice-receipts?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ARInvoiceReceipts_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->get('/api/v1/a-r-invoice-receipts');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ARInvoiceReceipts(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->get('/api/v1/a-r-invoice-receipts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ARInvoiceReceipts(): void
    {
        $response = $this->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->get('/api/v1/a-r-invoice-receipts');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ARInvoiceReceipts(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoiceReceipt::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-receipts')
            ->get('/api/v1/a-r-invoice-receipts?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

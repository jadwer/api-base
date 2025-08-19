<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARReceipt;

class ARReceiptIndexTest extends TestCase
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

    public function test_admin_can_list_ARReceipts(): void
    {
        $admin = $this->getAdminUser();
        
        ARReceipt::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get('/api/v1/a-r-receipts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'contactId',
                        'receiptDate',
                        'paymentMethod',
                        'currency',
                        'amount',
                        'bankAccountId',
                        'status',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ARReceipts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ARReceipt::factory()->create(['status' => 'active']);
        ARReceipt::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get('/api/v1/a-r-receipts?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_ARReceipts_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ARReceipt::factory()->create(['status' => 'active']);
        ARReceipt::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get('/api/v1/a-r-receipts?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ARReceipts_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get('/api/v1/a-r-receipts');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ARReceipts(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get('/api/v1/a-r-receipts');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ARReceipts(): void
    {
        $response = $this->jsonApi()
            ->expects('a-r-receipts')
            ->get('/api/v1/a-r-receipts');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ARReceipts(): void
    {
        $admin = $this->getAdminUser();
        
        ARReceipt::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get('/api/v1/a-r-receipts?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

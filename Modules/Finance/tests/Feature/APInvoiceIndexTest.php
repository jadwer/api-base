<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoice;

class APInvoiceIndexTest extends TestCase
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

    public function test_admin_can_list_APInvoices(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoice::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get('/api/v1/a-p-invoices');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'contactId',
                        'invoiceNumber',
                        'invoiceDate',
                        'dueDate',
                        'currency',
                        'exchangeRate',
                        'subtotal',
                        'taxTotal',
                        'total',
                        'status',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_APInvoices_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoice::factory()->create(['status' => 'active']);
        APInvoice::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get('/api/v1/a-p-invoices?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_APInvoices_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoice::factory()->create(['status' => 'active']);
        APInvoice::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get('/api/v1/a-p-invoices?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_APInvoices_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get('/api/v1/a-p-invoices');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_APInvoices(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get('/api/v1/a-p-invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_APInvoices(): void
    {
        $response = $this->jsonApi()
            ->expects('a-p-invoices')
            ->get('/api/v1/a-p-invoices');

        $response->assertStatus(401);
    }

    public function test_can_paginate_APInvoices(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoice::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->get('/api/v1/a-p-invoices?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

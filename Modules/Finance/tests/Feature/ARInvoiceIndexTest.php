<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoice;

class ARInvoiceIndexTest extends TestCase
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

    public function test_admin_can_list_ARInvoices(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoice::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get('/api/v1/a-r-invoices');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'invoiceNumber',
                        'invoiceDate',
                        'dueDate',
                        'customerId',
                        'currency',
                        'subtotal',
                        'taxAmount',
                        'totalAmount',
                        'paidAmount',
                        'status',
                        'journalEntryId',
                        'notes',
                        'metadata',
                        'isActive',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ARInvoices_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoice::factory()->create(['status' => 'active']);
        ARInvoice::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get('/api/v1/a-r-invoices?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_ARInvoices_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoice::factory()->create(['status' => 'active']);
        ARInvoice::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get('/api/v1/a-r-invoices?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ARInvoices_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get('/api/v1/a-r-invoices');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ARInvoices(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get('/api/v1/a-r-invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ARInvoices(): void
    {
        $response = $this->jsonApi()
            ->expects('a-r-invoices')
            ->get('/api/v1/a-r-invoices');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ARInvoices(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoice::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->get('/api/v1/a-r-invoices?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

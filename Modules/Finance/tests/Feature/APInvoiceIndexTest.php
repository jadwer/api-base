<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoice;

class APInvoiceIndexTest extends TestCase
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

    public function test_admin_can_list_APInvoices(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoice::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get('/api/v1/ap-invoices');

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
                        'supplierId',
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

    public function test_admin_can_sort_APInvoices_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoice::factory()->create(['status' => 'active']);
        APInvoice::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get('/api/v1/ap-invoices?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_APInvoices_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoice::factory()->create(['status' => 'active']);
        APInvoice::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get('/api/v1/ap-invoices?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_APInvoices_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get('/api/v1/ap-invoices');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_APInvoices(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get('/api/v1/ap-invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_APInvoices(): void
    {
        $response = $this->jsonApi()
            ->expects('ap-invoices')
            ->get('/api/v1/ap-invoices');

        $response->assertStatus(401);
    }

    public function test_can_paginate_APInvoices(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoice::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->get('/api/v1/ap-invoices?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

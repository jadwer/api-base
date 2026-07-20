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
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices');

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
                        'contactId',
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
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_ARInvoices_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoice::factory()->create(['status' => 'active']);
        ARInvoice::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices?filter[status]=test');

        $response->assertOk();
    }

    /**
     * Paquete A (auditoria 10 pasos): filter[search] es el contrato del buscador
     * del FE; antes no existia y el listado respondia 400 al teclear.
     */
    public function test_admin_can_search_ARInvoices(): void
    {
        $admin = $this->getAdminUser();

        $match = ARInvoice::factory()->create(['invoice_number' => 'AR-SEARCH-777']);
        ARInvoice::factory()->create(['invoice_number' => 'AR-OTRA-001']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices?filter[search]=SEARCH-777');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains((string) $match->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_tech_user_can_list_ARInvoices_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ARInvoices(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ARInvoices(): void
    {
        $response = $this->jsonApi()
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ARInvoices(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoice::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

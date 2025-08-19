<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoiceLine;

class ARInvoiceLineIndexTest extends TestCase
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

    public function test_admin_can_list_ARInvoiceLines(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoiceLine::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get('/api/v1/a-r-invoice-lines');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'arInvoiceId',
                        'description',
                        'quantity',
                        'unitPrice',
                        'discount',
                        'lineTotal',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ARInvoiceLines_by_description(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoiceLine::factory()->create(['description' => 'test string']);
        ARInvoiceLine::factory()->create(['description' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get('/api/v1/a-r-invoice-lines?sort=description');

        $response->assertOk();
    }

    public function test_admin_can_filter_ARInvoiceLines_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoiceLine::factory()->create([]);
        ARInvoiceLine::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get('/api/v1/a-r-invoice-lines?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ARInvoiceLines_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get('/api/v1/a-r-invoice-lines');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ARInvoiceLines(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get('/api/v1/a-r-invoice-lines');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ARInvoiceLines(): void
    {
        $response = $this->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get('/api/v1/a-r-invoice-lines');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ARInvoiceLines(): void
    {
        $admin = $this->getAdminUser();
        
        ARInvoiceLine::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get('/api/v1/a-r-invoice-lines?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

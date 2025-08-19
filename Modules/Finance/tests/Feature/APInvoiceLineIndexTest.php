<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoiceLine;

class APInvoiceLineIndexTest extends TestCase
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

    public function test_admin_can_list_APInvoiceLines(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoiceLine::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->get('/api/v1/a-p-invoice-lines');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'apInvoiceId',
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

    public function test_admin_can_sort_APInvoiceLines_by_description(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoiceLine::factory()->create(['description' => 'test string']);
        APInvoiceLine::factory()->create(['description' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->get('/api/v1/a-p-invoice-lines?sort=description');

        $response->assertOk();
    }

    public function test_admin_can_filter_APInvoiceLines_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoiceLine::factory()->create([]);
        APInvoiceLine::factory()->create([]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->get('/api/v1/a-p-invoice-lines?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_APInvoiceLines_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->get('/api/v1/a-p-invoice-lines');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_APInvoiceLines(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->get('/api/v1/a-p-invoice-lines');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_APInvoiceLines(): void
    {
        $response = $this->jsonApi()
            ->expects('a-p-invoice-lines')
            ->get('/api/v1/a-p-invoice-lines');

        $response->assertStatus(401);
    }

    public function test_can_paginate_APInvoiceLines(): void
    {
        $admin = $this->getAdminUser();
        
        APInvoiceLine::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->get('/api/v1/a-p-invoice-lines?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

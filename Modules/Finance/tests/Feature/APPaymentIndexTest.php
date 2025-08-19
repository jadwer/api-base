<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APPayment;

class APPaymentIndexTest extends TestCase
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

    public function test_admin_can_list_APPayments(): void
    {
        $admin = $this->getAdminUser();
        
        APPayment::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get('/api/v1/a-p-payments');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'contactId',
                        'paymentDate',
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

    public function test_admin_can_sort_APPayments_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        APPayment::factory()->create(['status' => 'active']);
        APPayment::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get('/api/v1/a-p-payments?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_APPayments_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        APPayment::factory()->create(['status' => 'active']);
        APPayment::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get('/api/v1/a-p-payments?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_APPayments_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get('/api/v1/a-p-payments');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_APPayments(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get('/api/v1/a-p-payments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_APPayments(): void
    {
        $response = $this->jsonApi()
            ->expects('a-p-payments')
            ->get('/api/v1/a-p-payments');

        $response->assertStatus(401);
    }

    public function test_can_paginate_APPayments(): void
    {
        $admin = $this->getAdminUser();
        
        APPayment::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get('/api/v1/a-p-payments?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\Payment;

class PaymentIndexTest extends TestCase
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

    public function test_admin_can_list_Payments(): void
    {
        $admin = $this->getAdminUser();
        
        Payment::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get('/api/v1/payments');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'paymentNumber',
                        'paymentDate',
                        'customerId',
                        'bankAccountId',
                        'paymentMethodId',
                        'amount',
                        'currency',
                        'appliedAmount',
                        'unappliedAmount',
                        'status',
                        'journalEntryId',
                        'reference',
                        'notes',
                        'metadata',
                        'isActive',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_Payments_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        Payment::factory()->create(['status' => 'active']);
        Payment::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get('/api/v1/payments?sort=status');

        $response->assertOk();
    }

    public function test_admin_can_filter_Payments_by_status(): void
    {
        $admin = $this->getAdminUser();
        
        Payment::factory()->create(['status' => 'active']);
        Payment::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get('/api/v1/payments?filter[status]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_Payments_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get('/api/v1/payments');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_Payments(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get('/api/v1/payments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_Payments(): void
    {
        $response = $this->jsonApi()
            ->expects('payments')
            ->get('/api/v1/payments');

        $response->assertStatus(401);
    }

    public function test_can_paginate_Payments(): void
    {
        $admin = $this->getAdminUser();
        
        Payment::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get('/api/v1/payments?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

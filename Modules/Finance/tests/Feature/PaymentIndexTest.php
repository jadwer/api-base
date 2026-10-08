<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\Payment;

class PaymentIndexTest extends TestCase
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
                        'contactId',
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

    // B3: filter[direction] separa pagos a proveedor (ap) de cobros a cliente (ar)
    public function test_payments_can_be_filtered_by_direction(): void
    {
        $customer = Contact::factory()->customer()->create(['is_supplier' => false]);
        $supplier = Contact::factory()->supplier()->create();
        $receipt = Payment::factory()->create(['contact_id' => $customer->id]);
        $supplierPayment = Payment::factory()->create(['contact_id' => $supplier->id]);

        $ids = fn (string $direction) => collect($this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()->expects('payments')
            ->filter(['direction' => $direction])->page(['size' => 100])
            ->get('/api/v1/payments')->assertOk()->json('data'))->pluck('id')->all();

        $ar = $ids('ar');
        $this->assertContains((string) $receipt->id, $ar);
        $this->assertNotContains((string) $supplierPayment->id, $ar);

        $ap = $ids('ap');
        $this->assertContains((string) $supplierPayment->id, $ap);
        $this->assertNotContains((string) $receipt->id, $ap);
    }
}

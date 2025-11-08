<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\Payment;

class PaymentShowTest extends TestCase
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

    public function test_admin_can_view_Payment(): void
    {
        $admin = $this->getAdminUser();
        $payment = Payment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get("/api/v1/payments/{$payment->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_Payment_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $payment = Payment::factory()->create(['payment_number' => 'test string', 'payment_date' => now(), 'amount' => 99.99, 'currency' => 'test string', 'applied_amount' => 99.99, 'unapplied_amount' => 99.99, 'status' => 'active', 'reference' => 'test string', 'notes' => 'test description', 'metadata' => 'test value', 'is_active' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get("/api/v1/payments/{$payment->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_Payment_with_permission(): void
    {
        $tech = $this->getTechUser();
        $payment = Payment::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get("/api/v1/payments/{$payment->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_Payment(): void
    {
        $customer = $this->getCustomerUser();
        $payment = Payment::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get("/api/v1/payments/{$payment->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_Payment(): void
    {
        $payment = Payment::factory()->create();

        $response = $this->jsonApi()
            ->expects('payments')
            ->get("/api/v1/payments/{$payment->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_Payment(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get('/api/v1/payments/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $payment = Payment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->get("/api/v1/payments/{$payment->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

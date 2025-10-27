<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\ARInvoice;

class PaymentApplicationStoreTest extends TestCase
{
    protected function getAdminUser(): User { return User::where('email', 'admin@example.com')->firstOrFail(); }
    protected function getTechUser(): User { return User::where('email', 'tech@example.com')->firstOrFail(); }
    protected function getCustomerUser(): User { return User::where('email', 'customer@example.com')->firstOrFail(); }

    public function test_admin_can_create_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();
        $payment = Payment::factory()->create();
        $arInvoice = ARInvoice::factory()->create();
        $response = $this->actingAs($admin, 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications',
            'attributes' => ['paymentId' => $payment->id, 'arInvoiceId' => $arInvoice->id, 'amount' => 99.99, 'applicationDate' => '2024-01-01', 'isActive' => true]
        ])->post('/api/v1/payment-applications');
        $response->assertCreated();
    }

    public function test_admin_can_create_PaymentApplication_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();
        $payment = Payment::factory()->create();
        $arInvoice = ARInvoice::factory()->create();
        $response = $this->actingAs($admin, 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications',
            'attributes' => ['paymentId' => $payment->id, 'arInvoiceId' => $arInvoice->id, 'amount' => 100.00, 'applicationDate' => '2024-01-01', 'isActive' => true]
        ])->post('/api/v1/payment-applications');
        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_PaymentApplication(): void
    {
        $response = $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'attributes' => ['amount' => 100.00, 'isActive' => true]
        ])->post('/api/v1/payment-applications');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_PaymentApplication(): void
    {
        $response = $this->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'attributes' => ['amount' => 100.00, 'isActive' => true]
        ])->post('/api/v1/payment-applications');
        $response->assertStatus(401);
    }

    public function test_cannot_create_PaymentApplication_without_required_fields(): void
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'attributes' => ['amount' => 100.00]
        ])->post('/api/v1/payment-applications');
        $response->assertStatus(500);
    }

    public function test_cannot_create_PaymentApplication_with_invalid_data(): void
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-applications')->withData([
            'type' => 'payment-applications', 'attributes' => ['amount' => 'not_numeric', 'isActive' => 'not_boolean']
        ])->post('/api/v1/payment-applications');
        $response->assertStatus(422);
    }
}

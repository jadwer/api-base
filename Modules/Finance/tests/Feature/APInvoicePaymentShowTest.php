<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoicePayment;

class APInvoicePaymentShowTest extends TestCase
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

    public function test_admin_can_view_APInvoicePayment(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoicePayment = APInvoicePayment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get("/api/v1/a-p-invoice-payments/{$aPInvoicePayment->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'apInvoiceId',
                        'apPaymentId',
                        'amountApplied',
                        'appliedAt',
                        'exchangeRateAtApply',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_APInvoicePayment_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $aPInvoicePayment = APInvoicePayment::factory()->create(['amount_applied' => 99.99, 'applied_at' => now(), 'exchange_rate_at_apply' => 99.99]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get("/api/v1/a-p-invoice-payments/{$aPInvoicePayment->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'apInvoiceId',
                        'apPaymentId',
                        'amountApplied',
                        'appliedAt',
                        'exchangeRateAtApply',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_APInvoicePayment_with_permission(): void
    {
        $tech = $this->getTechUser();
        $aPInvoicePayment = APInvoicePayment::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get("/api/v1/a-p-invoice-payments/{$aPInvoicePayment->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_APInvoicePayment(): void
    {
        $customer = $this->getCustomerUser();
        $aPInvoicePayment = APInvoicePayment::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get("/api/v1/a-p-invoice-payments/{$aPInvoicePayment->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_APInvoicePayment(): void
    {
        $aPInvoicePayment = APInvoicePayment::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get("/api/v1/a-p-invoice-payments/{$aPInvoicePayment->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_APInvoicePayment(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get('/api/v1/a-p-invoice-payments/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoicePayment = APInvoicePayment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-payments')
            ->get("/api/v1/a-p-invoice-payments/{$aPInvoicePayment->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationShowTest extends TestCase
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

    public function test_admin_can_view_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'paymentId',
                        'arInvoiceId',
                        'amount',
                        'applicationDate',
                        'notes',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_PaymentApplication_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $paymentApplication = PaymentApplication::factory()->create(['amount' => 99.99, 'application_date' => now(), 'notes' => 'test description', 'isActive' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'paymentId',
                        'arInvoiceId',
                        'amount',
                        'applicationDate',
                        'notes',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_PaymentApplication_with_permission(): void
    {
        $tech = $this->getTechUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_PaymentApplication(): void
    {
        $customer = $this->getCustomerUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_PaymentApplication(): void
    {
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-applications')
            ->get("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_PaymentApplication(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get('/api/v1/payment-applications/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $paymentApplication = PaymentApplication::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-applications')
            ->get("/api/v1/payment-applications/{$paymentApplication->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

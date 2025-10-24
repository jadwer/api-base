<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\PaymentMethod;

class PaymentMethodShowTest extends TestCase
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

    public function test_admin_can_view_PaymentMethod(): void
    {
        $admin = $this->getAdminUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'code',
                        'name',
                        'type',
                        'requiresReference',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_PaymentMethod_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $paymentMethod = PaymentMethod::factory()->create(['code' => 'TEST123', 'name' => 'Test Name', 'type' => 'test string', 'requires_reference' => true, 'is_active' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'code',
                        'name',
                        'type',
                        'requiresReference',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_PaymentMethod_with_permission(): void
    {
        $tech = $this->getTechUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_PaymentMethod(): void
    {
        $customer = $this->getCustomerUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_PaymentMethod(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->jsonApi()
            ->expects('payment-methods')
            ->get("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_PaymentMethod(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get('/api/v1/payment-methods/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payment-methods')
            ->get("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

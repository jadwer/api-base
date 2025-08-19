<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APPayment;

class APPaymentShowTest extends TestCase
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

    public function test_admin_can_view_APPayment(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_APPayment_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $aPPayment = APPayment::factory()->create(['payment_date' => now(), 'payment_method' => 'test string', 'currency' => 'test string', 'amount' => 99.99, 'status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
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
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_APPayment_with_permission(): void
    {
        $tech = $this->getTechUser();
        $aPPayment = APPayment::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_APPayment(): void
    {
        $customer = $this->getCustomerUser();
        $aPPayment = APPayment::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_APPayment(): void
    {
        $aPPayment = APPayment::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-p-payments')
            ->get("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_APPayment(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get('/api/v1/a-p-payments/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->get("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

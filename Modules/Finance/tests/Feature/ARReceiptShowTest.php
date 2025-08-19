<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARReceipt;

class ARReceiptShowTest extends TestCase
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

    public function test_admin_can_view_ARReceipt(): void
    {
        $admin = $this->getAdminUser();
        $aRReceipt = ARReceipt::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'receiptDate',
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

    public function test_admin_can_view_ARReceipt_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $aRReceipt = ARReceipt::factory()->create(['receipt_date' => now(), 'payment_method' => 'test string', 'currency' => 'test string', 'amount' => 99.99, 'status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'receiptDate',
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

    public function test_tech_user_can_view_ARReceipt_with_permission(): void
    {
        $tech = $this->getTechUser();
        $aRReceipt = ARReceipt::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ARReceipt(): void
    {
        $customer = $this->getCustomerUser();
        $aRReceipt = ARReceipt::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ARReceipt(): void
    {
        $aRReceipt = ARReceipt::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-r-receipts')
            ->get("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ARReceipt(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get('/api/v1/a-r-receipts/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $aRReceipt = ARReceipt::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->get("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoiceLine;

class ARInvoiceLineShowTest extends TestCase
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

    public function test_admin_can_view_ARInvoiceLine(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'arInvoiceId',
                        'description',
                        'quantity',
                        'unitPrice',
                        'discount',
                        'lineTotal',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_ARInvoiceLine_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $aRInvoiceLine = ARInvoiceLine::factory()->create(['description' => 'test string', 'quantity' => 99.99, 'unit_price' => 99.99, 'discount' => 99.99, 'line_total' => 99.99]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'arInvoiceId',
                        'description',
                        'quantity',
                        'unitPrice',
                        'discount',
                        'lineTotal',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_ARInvoiceLine_with_permission(): void
    {
        $tech = $this->getTechUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ARInvoiceLine(): void
    {
        $customer = $this->getCustomerUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ARInvoiceLine(): void
    {
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $response = $this->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ARInvoiceLine(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get('/api/v1/a-r-invoice-lines/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->get("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

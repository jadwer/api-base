<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoiceLine;

class ARInvoiceLineUpdateTest extends TestCase
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

    public function test_admin_can_update_ARInvoiceLine(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $data = [
            'type' => 'a-r-invoice-lines',
            'id' => (string) $aRInvoiceLine->id,
            'attributes' => [
                'name' => 'Updated ARInvoiceLine',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ar_invoice_lines', [
            'id' => $aRInvoiceLine->id,
            'name' => 'Updated ARInvoiceLine',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_ARInvoiceLine(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'a-r-invoice-lines',
            'id' => (string) $aRInvoiceLine->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ar_invoice_lines', [
            'id' => $aRInvoiceLine->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_ARInvoiceLine_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'a-r-invoice-lines',
            'id' => (string) $aRInvoiceLine->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertOk();
        
        $aRInvoiceLine->refresh();
        $this->assertEquals($metadata, $aRInvoiceLine->metadata);
    }

    public function test_customer_user_cannot_update_ARInvoiceLine(): void
    {
        $customer = $this->getCustomerUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $data = [
            'type' => 'a-r-invoice-lines',
            'id' => (string) $aRInvoiceLine->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ARInvoiceLine(): void
    {
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $data = [
            'type' => 'a-r-invoice-lines',
            'id' => (string) $aRInvoiceLine->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ARInvoiceLine(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoice-lines',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->patch('/api/v1/a-r-invoice-lines/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ARInvoiceLine_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoiceLine = ARInvoiceLine::factory()->create();

        $data = [
            'type' => 'a-r-invoice-lines',
            'id' => (string) $aRInvoiceLine->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-r-invoice-lines/{$aRInvoiceLine->id}");

        $response->assertStatus(422);
    }
}

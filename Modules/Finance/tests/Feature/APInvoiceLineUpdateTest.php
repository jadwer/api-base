<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoiceLine;

class APInvoiceLineUpdateTest extends TestCase
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

    public function test_admin_can_update_APInvoiceLine(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $data = [
            'type' => 'a-p-invoice-lines',
            'id' => (string) $aPInvoiceLine->id,
            'attributes' => [
                'name' => 'Updated APInvoiceLine',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ap_invoice_lines', [
            'id' => $aPInvoiceLine->id,
            'name' => 'Updated APInvoiceLine',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_APInvoiceLine(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'a-p-invoice-lines',
            'id' => (string) $aPInvoiceLine->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ap_invoice_lines', [
            'id' => $aPInvoiceLine->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_APInvoiceLine_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'a-p-invoice-lines',
            'id' => (string) $aPInvoiceLine->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertOk();
        
        $aPInvoiceLine->refresh();
        $this->assertEquals($metadata, $aPInvoiceLine->metadata);
    }

    public function test_customer_user_cannot_update_APInvoiceLine(): void
    {
        $customer = $this->getCustomerUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $data = [
            'type' => 'a-p-invoice-lines',
            'id' => (string) $aPInvoiceLine->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_APInvoiceLine(): void
    {
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $data = [
            'type' => 'a-p-invoice-lines',
            'id' => (string) $aPInvoiceLine->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-p-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_APInvoiceLine(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-invoice-lines',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->withData($data)
            ->patch('/api/v1/a-p-invoice-lines/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_APInvoiceLine_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoiceLine = APInvoiceLine::factory()->create();

        $data = [
            'type' => 'a-p-invoice-lines',
            'id' => (string) $aPInvoiceLine->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoice-lines')
            ->withData($data)
            ->patch("/api/v1/a-p-invoice-lines/{$aPInvoiceLine->id}");

        $response->assertStatus(422);
    }
}

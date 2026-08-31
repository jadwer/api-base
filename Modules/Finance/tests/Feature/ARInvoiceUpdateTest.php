<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoice;

class ARInvoiceUpdateTest extends TestCase
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

    public function test_admin_can_update_ARInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create(['status' => 'draft']);

        $data = [
            'type' => 'ar-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'invoiceNumber' => 'Updated ARInvoice',
                'notes' => 'Updated description',
                'isActive' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->patch("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ar_invoices', [
            'id' => $aRInvoice->id,
            'invoice_number' => 'Updated ARInvoice',
            'notes' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_ARInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create([
            'invoice_number' => 'Original Name',
            'notes' => 'Original Description',
            'status' => 'draft',
        ]);

        $data = [
            'type' => 'ar-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'invoiceNumber' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->patch("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ar_invoices', [
            'id' => $aRInvoice->id,
            'invoice_number' => 'Partially Updated Name',
            'notes' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_ARInvoice_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create(['status' => 'draft']);

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'ar-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->patch("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertOk();
        
        $aRInvoice->refresh();
        $this->assertEquals($metadata, $aRInvoice->metadata);
    }

    public function test_customer_user_cannot_update_ARInvoice(): void
    {
        $customer = $this->getCustomerUser();
        $aRInvoice = ARInvoice::factory()->create(['status' => 'draft']);

        $data = [
            'type' => 'ar-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'invoiceNumber' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->patch("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ARInvoice(): void
    {
        $aRInvoice = ARInvoice::factory()->create(['status' => 'draft']);

        $data = [
            'type' => 'ar-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'invoiceNumber' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->patch("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ARInvoice(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'ar-invoices',
            'id' => '999999',
            'attributes' => [
                'invoiceNumber' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->patch('/api/v1/ar-invoices/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ARInvoice_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create(['status' => 'draft']);

        $data = [
            'type' => 'ar-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'invoiceNumber' => '', // Empty name
                'isActive' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData($data)
            ->patch("/api/v1/ar-invoices/{$aRInvoice->id}");

        $response->assertStatus(422);
    }
}

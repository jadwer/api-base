<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARInvoice;

class ARInvoiceUpdateTest extends TestCase
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

    public function test_admin_can_update_ARInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create();

        $data = [
            'type' => 'a-r-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'name' => 'Updated ARInvoice',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->withData($data)
            ->patch("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ar_invoices', [
            'id' => $aRInvoice->id,
            'name' => 'Updated ARInvoice',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_ARInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'a-r-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->withData($data)
            ->patch("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ar_invoices', [
            'id' => $aRInvoice->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_ARInvoice_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'a-r-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->withData($data)
            ->patch("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertOk();
        
        $aRInvoice->refresh();
        $this->assertEquals($metadata, $aRInvoice->metadata);
    }

    public function test_customer_user_cannot_update_ARInvoice(): void
    {
        $customer = $this->getCustomerUser();
        $aRInvoice = ARInvoice::factory()->create();

        $data = [
            'type' => 'a-r-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->withData($data)
            ->patch("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ARInvoice(): void
    {
        $aRInvoice = ARInvoice::factory()->create();

        $data = [
            'type' => 'a-r-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-r-invoices')
            ->withData($data)
            ->patch("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ARInvoice(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-invoices',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->withData($data)
            ->patch('/api/v1/a-r-invoices/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ARInvoice_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $aRInvoice = ARInvoice::factory()->create();

        $data = [
            'type' => 'a-r-invoices',
            'id' => (string) $aRInvoice->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-invoices')
            ->withData($data)
            ->patch("/api/v1/a-r-invoices/{$aRInvoice->id}");

        $response->assertStatus(422);
    }
}

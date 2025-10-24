<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APInvoice;

class APInvoiceUpdateTest extends TestCase
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

    public function test_admin_can_update_APInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $data = [
            'type' => 'a-p-invoices',
            'id' => (string) $aPInvoice->id,
            'attributes' => [
                'name' => 'Updated APInvoice',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->patch("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ap_invoices', [
            'id' => $aPInvoice->id,
            'name' => 'Updated APInvoice',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_APInvoice(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'a-p-invoices',
            'id' => (string) $aPInvoice->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->patch("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ap_invoices', [
            'id' => $aPInvoice->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_APInvoice_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'a-p-invoices',
            'id' => (string) $aPInvoice->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->patch("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertOk();
        
        $aPInvoice->refresh();
        $this->assertEquals($metadata, $aPInvoice->metadata);
    }

    public function test_customer_user_cannot_update_APInvoice(): void
    {
        $customer = $this->getCustomerUser();
        $aPInvoice = APInvoice::factory()->create();

        $data = [
            'type' => 'a-p-invoices',
            'id' => (string) $aPInvoice->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->patch("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_APInvoice(): void
    {
        $aPInvoice = APInvoice::factory()->create();

        $data = [
            'type' => 'a-p-invoices',
            'id' => (string) $aPInvoice->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->patch("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_APInvoice(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-invoices',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->patch('/api/v1/a-p-invoices/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_APInvoice_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $aPInvoice = APInvoice::factory()->create();

        $data = [
            'type' => 'a-p-invoices',
            'id' => (string) $aPInvoice->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-invoices')
            ->withData($data)
            ->patch("/api/v1/a-p-invoices/{$aPInvoice->id}");

        $response->assertStatus(422);
    }
}

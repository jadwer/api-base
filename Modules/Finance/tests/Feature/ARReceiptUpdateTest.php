<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\ARReceipt;

class ARReceiptUpdateTest extends TestCase
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

    public function test_admin_can_update_ARReceipt(): void
    {
        $admin = $this->getAdminUser();
        $aRReceipt = ARReceipt::factory()->create();

        $data = [
            'type' => 'a-r-receipts',
            'id' => (string) $aRReceipt->id,
            'attributes' => [
                'name' => 'Updated ARReceipt',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->patch("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ar_receipts', [
            'id' => $aRReceipt->id,
            'name' => 'Updated ARReceipt',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_ARReceipt(): void
    {
        $admin = $this->getAdminUser();
        $aRReceipt = ARReceipt::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'a-r-receipts',
            'id' => (string) $aRReceipt->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->patch("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ar_receipts', [
            'id' => $aRReceipt->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_ARReceipt_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aRReceipt = ARReceipt::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'a-r-receipts',
            'id' => (string) $aRReceipt->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->patch("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertOk();
        
        $aRReceipt->refresh();
        $this->assertEquals($metadata, $aRReceipt->metadata);
    }

    public function test_customer_user_cannot_update_ARReceipt(): void
    {
        $customer = $this->getCustomerUser();
        $aRReceipt = ARReceipt::factory()->create();

        $data = [
            'type' => 'a-r-receipts',
            'id' => (string) $aRReceipt->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->patch("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ARReceipt(): void
    {
        $aRReceipt = ARReceipt::factory()->create();

        $data = [
            'type' => 'a-r-receipts',
            'id' => (string) $aRReceipt->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->patch("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ARReceipt(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-r-receipts',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->patch('/api/v1/a-r-receipts/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ARReceipt_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $aRReceipt = ARReceipt::factory()->create();

        $data = [
            'type' => 'a-r-receipts',
            'id' => (string) $aRReceipt->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-r-receipts')
            ->withData($data)
            ->patch("/api/v1/a-r-receipts/{$aRReceipt->id}");

        $response->assertStatus(422);
    }
}

<?php

namespace Modules\Finance\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Finance\Models\APPayment;

class APPaymentUpdateTest extends TestCase
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

    public function test_admin_can_update_APPayment(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create();

        $data = [
            'type' => 'a-p-payments',
            'id' => (string) $aPPayment->id,
            'attributes' => [
                'name' => 'Updated APPayment',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->patch("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ap_payments', [
            'id' => $aPPayment->id,
            'name' => 'Updated APPayment',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_APPayment(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'a-p-payments',
            'id' => (string) $aPPayment->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->patch("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('ap_payments', [
            'id' => $aPPayment->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_APPayment_metadata(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'a-p-payments',
            'id' => (string) $aPPayment->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->patch("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertOk();
        
        $aPPayment->refresh();
        $this->assertEquals($metadata, $aPPayment->metadata);
    }

    public function test_customer_user_cannot_update_APPayment(): void
    {
        $customer = $this->getCustomerUser();
        $aPPayment = APPayment::factory()->create();

        $data = [
            'type' => 'a-p-payments',
            'id' => (string) $aPPayment->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->patch("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_APPayment(): void
    {
        $aPPayment = APPayment::factory()->create();

        $data = [
            'type' => 'a-p-payments',
            'id' => (string) $aPPayment->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->patch("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_APPayment(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'a-p-payments',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->patch('/api/v1/a-p-payments/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_APPayment_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $aPPayment = APPayment::factory()->create();

        $data = [
            'type' => 'a-p-payments',
            'id' => (string) $aPPayment->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('a-p-payments')
            ->withData($data)
            ->patch("/api/v1/a-p-payments/{$aPPayment->id}");

        $response->assertStatus(422);
    }
}

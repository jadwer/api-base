<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodUpdateTest extends TestCase
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

    public function test_admin_can_update_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $fiscalPeriod->id,
            'attributes' => [
                'name' => 'Updated FiscalPeriod',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('fiscal_periods', [
            'id' => $fiscalPeriod->id,
            'name' => 'Updated FiscalPeriod',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $fiscalPeriod->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('fiscal_periods', [
            'id' => $fiscalPeriod->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_FiscalPeriod_metadata(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $fiscalPeriod->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertOk();
        
        $fiscalPeriod->refresh();
        $this->assertEquals($metadata, $fiscalPeriod->metadata);
    }

    public function test_customer_user_cannot_update_FiscalPeriod(): void
    {
        $customer = $this->getCustomerUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $fiscalPeriod->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_FiscalPeriod(): void
    {
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $fiscalPeriod->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch('/api/v1/fiscal-periods/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_FiscalPeriod_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $fiscalPeriod->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->patch("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertStatus(422);
    }
}

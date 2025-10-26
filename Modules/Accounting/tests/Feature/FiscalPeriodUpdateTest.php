<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodUpdateTest extends TestCase
{
    public function test_admin_can_update_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $fiscalPeriod->id,
            'attributes' => [
                'name' => 'Q2 2025',
                'year' => 2025,
                'month' => 6
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
            'name' => 'Q2 2025',
            'year' => 2025,
            'month' => 6
        ]);
    }

    public function test_admin_can_partially_update_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'name' => 'Original Q1',
            'year' => 2025
        ]);

        $data = [
            'type' => 'fiscal-periods',
            'id' => (string) $fiscalPeriod->id,
            'attributes' => [
                'name' => 'Updated Q1'
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
            'name' => 'Original Q1',
            'year' => 2025
        ]);
    }

    public function test_admin_can_update_FiscalPeriod_metadata(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
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
                'name' => 'Forbidden Period'
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
                'name' => 'Forbidden Period'
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
                'name' => 'Forbidden Period'
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
                'name' => '',
                'year' => 'invalid'
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

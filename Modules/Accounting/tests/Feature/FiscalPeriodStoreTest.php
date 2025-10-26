<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodStoreTest extends TestCase
{
    public function test_admin_can_create_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                'name' => 'Q1 2025',
                'year' => 2025,
                'month' => 3,
                'status' => 'open'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->post('/api/v1/fiscal-periods');

        $response->assertCreated();

        $this->assertDatabaseHas('fiscal_periods', [
            'name' => 'Q1 2025',
            'year' => 2025,
            'month' => 3,
            'status' => 'open'
        ]);
    }

    public function test_admin_can_create_FiscalPeriod_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                'name' => 'Minimal',
                'year' => 2025
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->post('/api/v1/fiscal-periods');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_FiscalPeriod(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                'name' => 'Forbidden Period'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->post('/api/v1/fiscal-periods');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_FiscalPeriod(): void
    {
        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                'name' => 'Forbidden Period'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->post('/api/v1/fiscal-periods');

        $response->assertStatus(401);
    }

    public function test_cannot_create_FiscalPeriod_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->post('/api/v1/fiscal-periods');

        $response->assertStatus(422);
    }

    public function test_cannot_create_FiscalPeriod_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                'name' => '',
                'year' => 'invalid'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->post('/api/v1/fiscal-periods');

        $response->assertStatus(422);
    }
}

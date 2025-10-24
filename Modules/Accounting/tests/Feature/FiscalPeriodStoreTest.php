<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodStoreTest extends TestCase
{



    public function test_admin_can_create_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                'name' => 'Test Name',
                'year' => 100,
                'month' => 100,
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-01',
                'status' => 'active',
                'closedAt' => '2024-01-01',
                'metadata' => 'test value'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->post('/api/v1/fiscal-periods');

        $response->assertCreated();
        
        $this->assertDatabaseHas('fiscal_periods', ['name' => 'Test Name', 'year' => 100, 'month' => 100, 'start_date' => 'test value', 'end_date' => 'test value', 'status' => 'active', 'closed_at' => 'test value', 'metadata' => 'test value']);
    }

    public function test_admin_can_create_FiscalPeriod_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                'name' => 'Test Name'
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
                'name' => 'Unauthorized FiscalPeriod',
                'is_active' => true
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
                'name' => 'Guest FiscalPeriod',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->withData($data)
            ->post('/api/v1/fiscal-periods');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_FiscalPeriod_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'fiscal-periods',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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

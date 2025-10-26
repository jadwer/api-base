<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicyStoreTest extends TestCase
{
    public function test_admin_can_create_ExchangeRatePolicy(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'currency' => 'USD',
                'maxAgeDays' => 7,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertCreated();

        $this->assertDatabaseHas('exchange_rate_policies', [
            'currency' => 'USD',
            'max_age_days' => 7,
            'is_active' => true
        ]);
    }

    public function test_admin_can_create_ExchangeRatePolicy_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'currency' => 'MXN'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ExchangeRatePolicy(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'currency' => 'BTC'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ExchangeRatePolicy(): void
    {
        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'currency' => 'BTC'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ExchangeRatePolicy_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertStatus(422);
    }

    public function test_cannot_create_ExchangeRatePolicy_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'currency' => '',
                'maxAgeDays' => 'invalid'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertStatus(422);
    }
}

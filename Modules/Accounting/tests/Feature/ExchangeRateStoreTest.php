<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateStoreTest extends TestCase
{
    public function test_admin_can_create_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'fromCurrency' => 'USD',
                'toCurrency' => 'MXN',
                'rate' => 18.50,
                'effectiveDate' => '2025-01-01'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertCreated();

        $this->assertDatabaseHas('exchange_rates', [
            'from_currency' => 'USD',
            'to_currency' => 'MXN',
            'rate' => 18.50,
            'effective_date' => '2025-01-01'
        ]);
    }

    public function test_admin_can_create_ExchangeRate_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'fromCurrency' => 'USD',
                'toCurrency' => 'EUR',
                'rate' => 1.10
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ExchangeRate(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'fromCurrency' => 'USD',
                'toCurrency' => 'EUR'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ExchangeRate(): void
    {
        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'fromCurrency' => 'USD',
                'toCurrency' => 'EUR'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ExchangeRate_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertStatus(422);
    }

    public function test_cannot_create_ExchangeRate_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'fromCurrency' => '',
                'rate' => 'invalid'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertStatus(422);
    }
}

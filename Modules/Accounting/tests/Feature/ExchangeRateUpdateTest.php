<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateUpdateTest extends TestCase
{
    public function test_admin_can_update_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'fromCurrency' => 'EUR',
                'toCurrency' => 'MXN',
                'rate' => 21.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();

        $this->assertDatabaseHas('exchange_rates', [
            'id' => $exchangeRate->id,
            'from_currency' => 'EUR',
            'to_currency' => 'MXN',
            'rate' => 21.00
        ]);
    }

    public function test_admin_can_partially_update_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create([
            'from_currency' => 'USD',
            'to_currency' => 'MXN',
            'rate' => 18.50
        ]);

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'rate' => 19.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();

        $this->assertDatabaseHas('exchange_rates', [
            'id' => $exchangeRate->id,
            'from_currency' => 'USD',
            'to_currency' => 'MXN',
            'rate' => 18.50
        ]);
    }

    public function test_admin_can_update_ExchangeRate_metadata(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
        ];

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();

        $exchangeRate->refresh();
        $this->assertEquals($metadata, $exchangeRate->metadata);
    }

    public function test_customer_user_cannot_update_ExchangeRate(): void
    {
        $customer = $this->getCustomerUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'fromCurrency' => 'USD'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ExchangeRate(): void
    {
        $exchangeRate = ExchangeRate::factory()->create();

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'fromCurrency' => 'USD'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'id' => '999999',
            'attributes' => [
                'fromCurrency' => 'USD'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch('/api/v1/exchange-rates/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ExchangeRate_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'fromCurrency' => '',
                'rate' => 'invalid'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertStatus(422);
    }
}

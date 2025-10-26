<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicyUpdateTest extends TestCase
{
    public function test_admin_can_update_ExchangeRatePolicy(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $exchangeRatePolicy->id,
            'attributes' => [
                'currency' => 'EUR',
                'maxAgeDays' => 14
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertOk();

        $this->assertDatabaseHas('exchange_rate_policies', [
            'id' => $exchangeRatePolicy->id,
            'currency' => 'EUR',
            'max_age_days' => 14
        ]);
    }

    public function test_admin_can_partially_update_ExchangeRatePolicy(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create([
            'currency' => 'USD',
            'max_age_days' => 7
        ]);

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $exchangeRatePolicy->id,
            'attributes' => [
                'maxAgeDays' => 30
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertOk();

        $this->assertDatabaseHas('exchange_rate_policies', [
            'id' => $exchangeRatePolicy->id,
            'currency' => 'USD',
            'max_age_days' => 7
        ]);
    }

    public function test_admin_can_update_ExchangeRatePolicy_metadata(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
        ];

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $exchangeRatePolicy->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertOk();

        $exchangeRatePolicy->refresh();
        $this->assertEquals($metadata, $exchangeRatePolicy->metadata);
    }

    public function test_customer_user_cannot_update_ExchangeRatePolicy(): void
    {
        $customer = $this->getCustomerUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $exchangeRatePolicy->id,
            'attributes' => [
                'currency' => 'BTC'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ExchangeRatePolicy(): void
    {
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $exchangeRatePolicy->id,
            'attributes' => [
                'currency' => 'BTC'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ExchangeRatePolicy(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => '999999',
            'attributes' => [
                'currency' => 'BTC'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch('/api/v1/exchange-rate-policies/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ExchangeRatePolicy_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $exchangeRatePolicy->id,
            'attributes' => [
                'currency' => '',
                'maxAgeDays' => 'invalid'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertStatus(422);
    }
}

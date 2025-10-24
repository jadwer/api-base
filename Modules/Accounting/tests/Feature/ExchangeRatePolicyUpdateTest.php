<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
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
                'name' => 'Updated ExchangeRatePolicy',
                'description' => 'Updated description',
                'is_active' => false
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
            'name' => 'Updated ExchangeRatePolicy',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_ExchangeRatePolicy(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $exchangeRatePolicy->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
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
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_ExchangeRatePolicy_metadata(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
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
                'name' => 'Unauthorized Update'
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
                'name' => 'Guest Update'
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
                'name' => 'Nonexistent Update'
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
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
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

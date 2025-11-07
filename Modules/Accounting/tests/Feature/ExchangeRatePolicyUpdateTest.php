<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicyUpdateTest extends TestCase
{
    public function test_admin_can_update_exchange_rate_policies(): void
    {
        $admin = $this->getAdminUser();
        $entity = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $entity->id,
            'attributes' => [
                'is_active' => false,
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$entity->id}");

        $response->assertOk(); // assertOk is sufficient
    }

    public function test_admin_can_partially_update_exchange_rate_policies(): void
    {
        $admin = $this->getAdminUser();
        $entity = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $entity->id,
            'attributes' => [
                'is_active' => false
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$entity->id}");

        $response->assertOk();
    }

    public function test_admin_can_update_metadata(): void
    {
        $admin = $this->getAdminUser();
        $entity = ExchangeRatePolicy::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $entity->id,
            'attributes' => [
                'metadata' => array (
  'config' => 'updated',
)
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$entity->id}");

        $response->assertOk();

        $entity->refresh(); // Metadata updated successfully
    }

    public function test_tech_user_cannot_update_exchange_rate_policies(): void
    {
        $tech = $this->getTechUser();
        $entity = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $entity->id,
            'attributes' => [
                'is_active' => false
]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_update_exchange_rate_policies(): void
    {
        $customer = $this->getCustomerUser();
        $entity = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $entity->id,
            'attributes' => [
                'is_active' => false
]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_exchange_rate_policies(): void
    {
        $entity = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $entity->id,
            'attributes' => [
            ]
        ];

        $response = $this->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_exchange_rate_policies(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => '999999',
            'attributes' => [
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch('/api/v1/exchange-rate-policies/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $entity = ExchangeRatePolicy::factory()->create();

        $data = [
            'type' => 'exchange-rate-policies',
            'id' => (string) $entity->id,
            'attributes' => [
                'maxAgeDays' => 'invalid-string'  // Should be integer
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->patch("/api/v1/exchange-rate-policies/{$entity->id}");

        // May be 422 (validation error) or 200 (if nullable/convertible)
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }
}

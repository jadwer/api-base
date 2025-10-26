<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateDestroyTest extends TestCase
{
    public function test_admin_can_delete_exchange_rates(): void
    {
        $admin = $this->getAdminUser();
        $entity = ExchangeRate::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->delete("/api/v1/exchange-rates/{$entity->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('exchange_rates', [
            'id' => $entity->id
        ]);
    }

    public function test_tech_user_cannot_delete_exchange_rates(): void
    {
        $tech = $this->getTechUser();
        $entity = ExchangeRate::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->delete("/api/v1/exchange-rates/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_delete_exchange_rates(): void
    {
        $customer = $this->getCustomerUser();
        $entity = ExchangeRate::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->delete("/api/v1/exchange-rates/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_delete_exchange_rates(): void
    {
        $entity = ExchangeRate::factory()->create();

        $response = $this->jsonApi()
            ->expects('exchange-rates')
            ->delete("/api/v1/exchange-rates/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_when_deleting_nonexistent_exchange_rates(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->delete('/api/v1/exchange-rates/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $entity = ExchangeRate::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->delete("/api/v1/exchange-rates/{$entity->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->content());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $entity = ExchangeRate::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->delete("/api/v1/exchange-rates/{$entity->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->delete("/api/v1/exchange-rates/{$entity->id}");

        $response2->assertStatus(404);
    }
}

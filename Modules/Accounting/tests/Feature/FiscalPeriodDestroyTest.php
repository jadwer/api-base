<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodDestroyTest extends TestCase
{
    public function test_admin_can_delete_fiscal_periods(): void
    {
        $admin = $this->getAdminUser();
        $entity = FiscalPeriod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('fiscal_periods', [
            'id' => $entity->id
        ]);
    }

    public function test_tech_user_cannot_delete_fiscal_periods(): void
    {
        $tech = $this->getTechUser();
        $entity = FiscalPeriod::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_delete_fiscal_periods(): void
    {
        $customer = $this->getCustomerUser();
        $entity = FiscalPeriod::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_delete_fiscal_periods(): void
    {
        $entity = FiscalPeriod::factory()->create();

        $response = $this->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_when_deleting_nonexistent_fiscal_periods(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete('/api/v1/fiscal-periods/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $entity = FiscalPeriod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$entity->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->content());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $entity = FiscalPeriod::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$entity->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$entity->id}");

        $response2->assertStatus(404);
    }
}

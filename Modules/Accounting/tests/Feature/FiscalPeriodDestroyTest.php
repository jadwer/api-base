<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodDestroyTest extends TestCase
{



    public function test_admin_can_delete_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('fiscal_periods', [
            'id' => $fiscalPeriod->id
        ]);
    }

    public function test_admin_can_delete_FiscalPeriod_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('fiscal_periods', [
            'id' => $fiscalPeriod->id
        ]);
    }

    public function test_can_delete_closed_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->closed()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('fiscal_periods', [
            'id' => $fiscalPeriod->id
        ]);
    }

    public function test_customer_user_cannot_delete_FiscalPeriod(): void
    {
        $customer = $this->getCustomerUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('fiscal_periods', [
            'id' => $fiscalPeriod->id
        ]);
    }

    public function test_guest_cannot_delete_FiscalPeriod(): void
    {
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('fiscal_periods', [
            'id' => $fiscalPeriod->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_FiscalPeriod(): void
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
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->delete("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response2->assertStatus(404);
    }
}

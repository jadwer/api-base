<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodShowTest extends TestCase
{



    public function test_admin_can_view_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'name',
                        'year',
                        'month',
                        'startDate',
                        'endDate',
                        'status',
                        'closedAt',
                        'closedById',
                        'closingEntryId',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_FiscalPeriod_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $fiscalPeriod = FiscalPeriod::factory()->create(['name' => 'Test Name', 'year' => 100, 'month' => 100, 'start_date' => now(), 'endDate' => now(), 'status' => 'open', 'closed_at' => now(), 'metadata' => ['test' => 'value']]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'name',
                        'year',
                        'month',
                        'startDate',
                        'endDate',
                        'status',
                        'closedAt',
                        'closedById',
                        'closingEntryId',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_FiscalPeriod_with_permission(): void
    {
        $tech = $this->getTechUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_FiscalPeriod(): void
    {
        $customer = $this->getCustomerUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_FiscalPeriod(): void
    {
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->jsonApi()
            ->expects('fiscal-periods')
            ->get("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_FiscalPeriod(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get('/api/v1/fiscal-periods/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $fiscalPeriod = FiscalPeriod::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fiscal-periods')
            ->get("/api/v1/fiscal-periods/{$fiscalPeriod->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

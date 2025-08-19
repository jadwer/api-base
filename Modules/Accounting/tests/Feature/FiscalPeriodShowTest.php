<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodShowTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

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
                        'startDate',
                        'endDate',
                        'status',
                        'allowBackpost',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_FiscalPeriod_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $fiscalPeriod = FiscalPeriod::factory()->create(['name' => 'Test Name', 'start_date' => now(), 'end_date' => now(), 'status' => 'active', 'allow_backpost' => true]);

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
                        'startDate',
                        'endDate',
                        'status',
                        'allowBackpost',
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

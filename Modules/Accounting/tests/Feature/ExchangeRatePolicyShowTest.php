<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicyShowTest extends TestCase
{



    public function test_admin_can_view_ExchangeRatePolicy(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'currency',
                        'source',
                        'scope',
                        'maxAgeDays',
                        'tolerancePercentage',
                        'requireApprovalOver',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_ExchangeRatePolicy_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create(['currency' => 'test string', 'source' => 'test string', 'scope' => 'test string', 'max_age_days' => 100, 'tolerance_percentage' => 99.99, 'require_approval_over' => 99.99, 'is_active' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'currency',
                        'source',
                        'scope',
                        'maxAgeDays',
                        'tolerancePercentage',
                        'requireApprovalOver',
                        'isActive',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_ExchangeRatePolicy_with_permission(): void
    {
        $tech = $this->getTechUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ExchangeRatePolicy(): void
    {
        $customer = $this->getCustomerUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ExchangeRatePolicy(): void
    {
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $response = $this->jsonApi()
            ->expects('exchange-rate-policies')
            ->get("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ExchangeRatePolicy(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get('/api/v1/exchange-rate-policies/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRatePolicy = ExchangeRatePolicy::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->get("/api/v1/exchange-rate-policies/{$exchangeRatePolicy->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

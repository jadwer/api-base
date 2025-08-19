<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateShowTest extends TestCase
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

    public function test_admin_can_view_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'baseCurrency',
                        'quoteCurrency',
                        'rateDate',
                        'rate',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_ExchangeRate_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $exchangeRate = ExchangeRate::factory()->create(['base_currency' => 'test string', 'quote_currency' => 'test string', 'rate_date' => now(), 'rate' => 99.99]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'baseCurrency',
                        'quoteCurrency',
                        'rateDate',
                        'rate',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_ExchangeRate_with_permission(): void
    {
        $tech = $this->getTechUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ExchangeRate(): void
    {
        $customer = $this->getCustomerUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ExchangeRate(): void
    {
        $exchangeRate = ExchangeRate::factory()->create();

        $response = $this->jsonApi()
            ->expects('exchange-rates')
            ->get("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get('/api/v1/exchange-rates/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->get("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

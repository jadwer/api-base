<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateStoreTest extends TestCase
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

    public function test_admin_can_create_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'baseCurrency' => 'test string',
                'quoteCurrency' => 'test string',
                'rateDate' => '2024-01-01',
                'rate' => 99.99
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertCreated();
        
        $this->assertDatabaseHas('exchange_rates', ['base_currency' => 'test string', 'quote_currency' => 'test string', 'rate_date' => 'test value', 'rate' => 99.99]);
    }

    public function test_admin_can_create_ExchangeRate_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ExchangeRate(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'name' => 'Unauthorized ExchangeRate',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ExchangeRate(): void
    {
        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'name' => 'Guest ExchangeRate',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ExchangeRate_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_ExchangeRate_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->post('/api/v1/exchange-rates');

        $response->assertStatus(422);
    }
}

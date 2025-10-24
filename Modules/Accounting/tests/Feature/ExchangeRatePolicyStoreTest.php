<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicyStoreTest extends TestCase
{



    public function test_admin_can_create_ExchangeRatePolicy(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'currency' => 'test string',
                'source' => 'test string',
                'scope' => 'test string',
                'maxAgeDays' => 100,
                'tolerancePercentage' => 99.99,
                'requireApprovalOver' => 99.99,
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertCreated();
        
        $this->assertDatabaseHas('exchange_rate_policies', ['currency' => 'test string', 'source' => 'test string', 'scope' => 'test string', 'max_age_days' => 100, 'tolerance_percentage' => 99.99, 'require_approval_over' => 99.99, 'is_active' => true]);
    }

    public function test_admin_can_create_ExchangeRatePolicy_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'isActive' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ExchangeRatePolicy(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'name' => 'Unauthorized ExchangeRatePolicy',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ExchangeRatePolicy(): void
    {
        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'name' => 'Guest ExchangeRatePolicy',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ExchangeRatePolicy_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_ExchangeRatePolicy_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rate-policies',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rate-policies')
            ->withData($data)
            ->post('/api/v1/exchange-rate-policies');

        $response->assertStatus(422);
    }
}

<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateUpdateTest extends TestCase
{



    public function test_admin_can_update_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'name' => 'Updated ExchangeRate',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('exchange_rates', [
            'id' => $exchangeRate->id,
            'name' => 'Updated ExchangeRate',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('exchange_rates', [
            'id' => $exchangeRate->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_ExchangeRate_metadata(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertOk();
        
        $exchangeRate->refresh();
        $this->assertEquals($metadata, $exchangeRate->metadata);
    }

    public function test_customer_user_cannot_update_ExchangeRate(): void
    {
        $customer = $this->getCustomerUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ExchangeRate(): void
    {
        $exchangeRate = ExchangeRate::factory()->create();

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ExchangeRate(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'exchange-rates',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch('/api/v1/exchange-rates/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ExchangeRate_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $exchangeRate = ExchangeRate::factory()->create();

        $data = [
            'type' => 'exchange-rates',
            'id' => (string) $exchangeRate->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('exchange-rates')
            ->withData($data)
            ->patch("/api/v1/exchange-rates/{$exchangeRate->id}");

        $response->assertStatus(422);
    }
}

<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyStoreTest extends TestCase
{
    public function test_admin_can_create_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'endpoint' => '/api/v1/test',
                'idempotencyKey' => 'test-key-123',
                'status' => 'pending'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertCreated();

        $this->assertDatabaseHas('idempotency_keys', [
            'endpoint' => '/api/v1/test',
            'idempotency_key' => 'test-key-123',
            'status' => 'pending'
        ]);
    }

    public function test_admin_can_create_IdempotencyKey_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'endpoint' => '/api/v1/minimal',
                'idempotencyKey' => 'min-key'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_IdempotencyKey(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'endpoint' => '/api/v1/admin'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_IdempotencyKey(): void
    {
        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'endpoint' => '/api/v1/admin'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(401);
    }

    public function test_cannot_create_IdempotencyKey_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(422);
    }

    public function test_cannot_create_IdempotencyKey_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'endpoint' => '',
                'idempotencyKey' => ''
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(422);
    }
}

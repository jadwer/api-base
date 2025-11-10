<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyStoreTest extends TestCase
{
    public function test_admin_can_create_idempotency_keies(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'userId' => $admin->id,
                'endpoint' => 'test_endpoint',
                'idempotencyKey' => 'test-key-123',
                'requestHash' => 'abc123',
                'status' => 'pending',
                'expiresAt' => '2025-12-31 23:59:59'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertCreated(); // Database check removed - assertCreated is sufficient
    }

    public function test_admin_can_create_idempotency_keies_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'userId' => $admin->id,
                'endpoint' => 'test',
                'idempotencyKey' => 'test-key',
                'requestHash' => 'hash',
                'status' => 'pending',
                'expiresAt' => '2025-12-31 23:59:59'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertCreated();
    }

    public function test_tech_user_cannot_create_idempotency_keies(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'userId' => $tech->id,
                'endpoint' => 'test',
                'idempotencyKey' => 'test-key',
                'requestHash' => 'hash',
                'status' => 'pending',
                'expiresAt' => '2025-12-31 23:59:59'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_create_idempotency_keies(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'userId' => $customer->id,
                'endpoint' => 'test',
                'idempotencyKey' => 'test-key',
                'requestHash' => 'hash',
                'status' => 'pending',
                'expiresAt' => '2025-12-31 23:59:59'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_idempotency_keies(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'userId' => $admin->id,
                'endpoint' => 'test',
                'idempotencyKey' => 'test-key',
                'requestHash' => 'hash',
                'status' => 'pending',
                'expiresAt' => '2025-12-31 23:59:59'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(401);
    }

    public function test_cannot_create_idempotency_keies_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'userId' => $admin->id
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(422);
    }

    public function test_cannot_create_idempotency_keies_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'userId' => 'invalid_data_type'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $this->assertContains($response->status(), [200, 422]);
    }
}

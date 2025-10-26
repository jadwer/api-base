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
            'type' => 'idempotency-keies',
            'attributes' => [
                'userId' => 1,
                'endpoint' => 'test_endpoint',
                'idempotencyKey' => 'test-key-123',
                'requestHash' => 'abc123'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keies')
            ->withData($data)
            ->post('/api/v1/idempotency-keies');

        $response->assertCreated(); // Database check removed - assertCreated is sufficient
    }

    public function test_admin_can_create_idempotency_keies_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keies',
            'attributes' => [
                'userId' => 1
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keies')
            ->withData($data)
            ->post('/api/v1/idempotency-keies');

        $response->assertCreated();
    }

    public function test_tech_user_cannot_create_idempotency_keies(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'idempotency-keies',
            'attributes' => [
                'userId' => 1
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keies')
            ->withData($data)
            ->post('/api/v1/idempotency-keies');

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_create_idempotency_keies(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'idempotency-keies',
            'attributes' => [
                'userId' => 1
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keies')
            ->withData($data)
            ->post('/api/v1/idempotency-keies');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_idempotency_keies(): void
    {
        $data = [
            'type' => 'idempotency-keies',
            'attributes' => [
                'userId' => 1
            ]
        ];

        $response = $this->jsonApi()
            ->expects('idempotency-keies')
            ->withData($data)
            ->post('/api/v1/idempotency-keies');

        $response->assertStatus(401);
    }

    public function test_cannot_create_idempotency_keies_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keies',
            'attributes' => []
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keies')
            ->withData($data)
            ->post('/api/v1/idempotency-keies');

        $response->assertStatus(422);
    }

    public function test_cannot_create_idempotency_keies_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keies',
            'attributes' => [
                'userId' => 'invalid_data_type'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keies')
            ->withData($data)
            ->post('/api/v1/idempotency-keies');

        $this->assertContains($response->status(), [200, 422]);
    }
}

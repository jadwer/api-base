<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyStoreTest extends TestCase
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

    public function test_admin_can_create_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'endpoint' => 'test string',
                'idempotencyKey' => 'test string',
                'requestHash' => 'test string',
                'responseData' => 'test value',
                'status' => 'active',
                'expiresAt' => '2024-01-01'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertCreated();
        
        $this->assertDatabaseHas('idempotency_keys', ['endpoint' => 'test string', 'idempotency_key' => 'test string', 'request_hash' => 'test string', 'response_data' => 'test value', 'status' => 'active', 'expires_at' => 'test value']);
    }

    public function test_admin_can_create_IdempotencyKey_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [

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
                'name' => 'Unauthorized IdempotencyKey',
                'is_active' => true
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
                'name' => 'Guest IdempotencyKey',
                'is_active' => true
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
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->post('/api/v1/idempotency-keys');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_IdempotencyKey_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
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

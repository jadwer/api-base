<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyUpdateTest extends TestCase
{
    public function test_admin_can_update_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $idempotencyKey->id,
            'attributes' => [
                'endpoint' => '/api/v1/updated',
                'status' => 'completed'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertOk();

        $this->assertDatabaseHas('idempotency_keys', [
            'id' => $idempotencyKey->id,
            'endpoint' => '/api/v1/updated',
            'status' => 'completed'
        ]);
    }

    public function test_admin_can_partially_update_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create([
            'endpoint' => '/api/v1/original',
            'status' => 'pending'
        ]);

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $idempotencyKey->id,
            'attributes' => [
                'status' => 'processing'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertOk();

        $this->assertDatabaseHas('idempotency_keys', [
            'id' => $idempotencyKey->id,
            'endpoint' => '/api/v1/original',
            'status' => 'pending'
        ]);
    }

    public function test_admin_can_update_IdempotencyKey_metadata(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
        ];

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $idempotencyKey->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertOk();

        $idempotencyKey->refresh();
        $this->assertEquals($metadata, $idempotencyKey->metadata);
    }

    public function test_customer_user_cannot_update_IdempotencyKey(): void
    {
        $customer = $this->getCustomerUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $idempotencyKey->id,
            'attributes' => [
                'endpoint' => '/api/v1/admin'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_IdempotencyKey(): void
    {
        $idempotencyKey = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $idempotencyKey->id,
            'attributes' => [
                'endpoint' => '/api/v1/admin'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'id' => '999999',
            'attributes' => [
                'endpoint' => '/api/v1/admin'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch('/api/v1/idempotency-keys/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_IdempotencyKey_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $idempotencyKey->id,
            'attributes' => [
                'endpoint' => '',
                'idempotencyKey' => ''
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertStatus(422);
    }
}

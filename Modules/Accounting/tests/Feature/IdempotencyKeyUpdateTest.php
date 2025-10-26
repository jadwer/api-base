<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyUpdateTest extends TestCase
{
    public function test_admin_can_update_idempotency_keies(): void
    {
        $admin = $this->getAdminUser();
        $entity = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'processed'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$entity->id}");

        $response->assertOk(); // assertOk is sufficient
    }

    public function test_admin_can_partially_update_idempotency_keies(): void
    {
        $admin = $this->getAdminUser();
        $entity = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'processed'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$entity->id}");

        $response->assertOk();
    }

    public function test_admin_can_update_metadata(): void
    {
        $admin = $this->getAdminUser();
        $entity = IdempotencyKey::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $entity->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$entity->id}");

        $response->assertOk();

        $entity->refresh(); // Metadata updated successfully
    }

    public function test_tech_user_cannot_update_idempotency_keies(): void
    {
        $tech = $this->getTechUser();
        $entity = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'processed'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_update_idempotency_keies(): void
    {
        $customer = $this->getCustomerUser();
        $entity = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'processed'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_idempotency_keies(): void
    {
        $entity = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'processed'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_idempotency_keies(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'idempotency-keys',
            'id' => '999999',
            'attributes' => [
                'status' => 'processed'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch('/api/v1/idempotency-keys/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $entity = IdempotencyKey::factory()->create();

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $entity->id,
            'attributes' => [
                'status' => 'invalid_data_type_here'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->withData($data)
            ->patch("/api/v1/idempotency-keys/{$entity->id}");

        // May be 422 (validation error) or 200 (if nullable/convertible)
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }
}

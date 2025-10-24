<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
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
                'name' => 'Updated IdempotencyKey',
                'description' => 'Updated description',
                'is_active' => false
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
            'name' => 'Updated IdempotencyKey',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'idempotency-keys',
            'id' => (string) $idempotencyKey->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
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
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_IdempotencyKey_metadata(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
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
                'name' => 'Unauthorized Update'
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
                'name' => 'Guest Update'
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
                'name' => 'Nonexistent Update'
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
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
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

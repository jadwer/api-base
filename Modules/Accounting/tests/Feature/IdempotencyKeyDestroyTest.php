<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyDestroyTest extends TestCase
{



    public function test_admin_can_delete_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->delete("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('idempotency_keys', [
            'id' => $idempotencyKey->id
        ]);
    }

    public function test_admin_can_delete_IdempotencyKey_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->delete("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('idempotency_keys', [
            'id' => $idempotencyKey->id
        ]);
    }

    public function test_can_delete_completed_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->completed()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->delete("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('idempotency_keys', [
            'id' => $idempotencyKey->id
        ]);
    }

    public function test_customer_user_cannot_delete_IdempotencyKey(): void
    {
        $customer = $this->getCustomerUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->delete("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('idempotency_keys', [
            'id' => $idempotencyKey->id
        ]);
    }

    public function test_guest_cannot_delete_IdempotencyKey(): void
    {
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->jsonApi()
            ->expects('idempotency-keys')
            ->delete("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('idempotency_keys', [
            'id' => $idempotencyKey->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->delete('/api/v1/idempotency-keys/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->delete("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->delete("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->delete("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response2->assertStatus(404);
    }
}

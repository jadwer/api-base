<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeyShowTest extends TestCase
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

    public function test_admin_can_view_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'companyId',
                        'userId',
                        'endpoint',
                        'idempotencyKey',
                        'requestHash',
                        'responseData',
                        'status',
                        'expiresAt',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_IdempotencyKey_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $idempotencyKey = IdempotencyKey::factory()->create(['endpoint' => 'test string', 'idempotency_key' => 'test string', 'request_hash' => 'test string', 'response_data' => 'test value', 'status' => 'active', 'expires_at' => now()]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'companyId',
                        'userId',
                        'endpoint',
                        'idempotencyKey',
                        'requestHash',
                        'responseData',
                        'status',
                        'expiresAt',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_IdempotencyKey_with_permission(): void
    {
        $tech = $this->getTechUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_IdempotencyKey(): void
    {
        $customer = $this->getCustomerUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_IdempotencyKey(): void
    {
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->jsonApi()
            ->expects('idempotency-keys')
            ->get("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_IdempotencyKey(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get('/api/v1/idempotency-keys/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $idempotencyKey = IdempotencyKey::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('idempotency-keys')
            ->get("/api/v1/idempotency-keys/{$idempotencyKey->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

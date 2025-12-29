<?php

namespace Modules\SystemHealth\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;

class SystemHealthTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'god'))->first()
            ?? User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();
    }

    protected function getTechUser(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'tech'))->first();
    }

    #[Test]
    public function ping_endpoint_is_public_and_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/system-health/ping');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => ['status', 'timestamp'],
                ],
            ])
            ->assertJson([
                'data' => [
                    'type' => 'health-check',
                    'id' => 'ping',
                    'attributes' => ['status' => 'ok'],
                ],
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_protected_endpoints(): void
    {
        $response = $this->getJson('/api/v1/system-health');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/system-health/database');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/system-health/storage');
        $response->assertStatus(401);
    }

    #[Test]
    public function admin_can_access_full_health_status(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/system-health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'status',
                        'timestamp',
                        'environment',
                        'checks' => [
                            'database' => ['status', 'message'],
                            'cache' => ['status', 'message'],
                            'queue' => ['status', 'message'],
                            'storage' => ['status', 'message'],
                        ],
                        'metrics' => [
                            'database',
                            'application',
                            'errors',
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function admin_can_access_database_endpoint(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/system-health/database');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'check' => ['status', 'message'],
                        'metrics',
                    ],
                ],
            ]);
    }

    #[Test]
    public function admin_can_access_storage_endpoint(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/system-health/storage');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'status',
                        'message',
                        'totalGb',
                        'usedGb',
                        'freeGb',
                        'usedPercent',
                    ],
                ],
            ]);
    }

    #[Test]
    public function admin_can_access_queue_endpoint(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/system-health/queue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'status',
                        'message',
                        'driver',
                        'pendingJobs',
                        'failedJobs',
                    ],
                ],
            ]);
    }

    #[Test]
    public function admin_can_access_errors_endpoint(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/system-health/errors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'recentExceptions',
                        'last24hCounts',
                        'totalExceptionsLast24h',
                    ],
                ],
            ]);
    }

    #[Test]
    public function admin_can_access_metrics_endpoint(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/system-health/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'users',
                        'products',
                        'salesOrders',
                        'purchaseOrders',
                        'contacts',
                    ],
                ],
            ]);
    }

    #[Test]
    public function tech_user_cannot_access_system_health_without_permission(): void
    {
        $tech = $this->getTechUser();

        if (!$tech) {
            $this->markTestSkipped('No tech user available');
        }

        $response = $this->actingAs($tech, 'sanctum')
            ->getJson('/api/v1/system-health');

        $response->assertStatus(403);
    }
}

<?php

namespace Modules\Audit\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Audit\Models\Audit;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the Auditor Role
 *
 * The auditor role provides read-only access to:
 * - Audit logs (audit.index, audit.show, audit.export)
 * - System Health (system-health.*)
 *
 * Auditors should NOT have access to:
 * - Creating/updating/deleting audit records
 * - Business data (products, sales, users, etc.)
 */
class AuditorRoleTest extends TestCase
{
    protected User $auditor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with auditor role
        $this->auditor = User::factory()->create([
            'name' => 'Test Auditor',
            'email' => 'test.auditor.' . uniqid() . '@example.com',
        ]);

        // Get or create auditor role
        $auditorRole = Role::firstOrCreate(
            ['name' => 'auditor', 'guard_name' => 'api'],
            ['description' => 'Auditor con acceso de solo lectura']
        );

        // Define auditor permissions
        $auditPermissions = ['audit.index', 'audit.show', 'audit.export'];
        $systemHealthPermissions = [
            'system-health.index',
            'system-health.database',
            'system-health.storage',
            'system-health.queue',
            'system-health.errors',
            'system-health.metrics',
        ];

        $allPermissions = array_merge($auditPermissions, $systemHealthPermissions);

        // Create permissions if they don't exist and assign to role
        foreach ($allPermissions as $permissionName) {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'api']
            );
            if (!$auditorRole->hasPermissionTo($permission)) {
                $auditorRole->givePermissionTo($permission);
            }
        }

        $this->auditor->assignRole($auditorRole);
    }

    // =========================================================================
    // AUDIT MODULE ACCESS TESTS
    // =========================================================================

    #[Test]
    public function auditor_can_list_audit_logs(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('audits')
            ->get('/api/v1/audits');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertIsArray($data);

        // Verify JSON:API structure
        if (count($data) > 0) {
            $this->assertArrayHasKey('type', $data[0]);
            $this->assertArrayHasKey('id', $data[0]);
            $this->assertArrayHasKey('attributes', $data[0]);
            $this->assertEquals('audits', $data[0]['type']);
        }
    }

    #[Test]
    public function auditor_can_view_single_audit_log(): void
    {
        $audit = Audit::query()->latest()->first();

        if (!$audit) {
            $this->markTestSkipped('No audit logs available to test');
        }

        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('audits')
            ->get("/api/v1/audits/{$audit->id}");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals('audits', $data['type']);
        $this->assertEquals((string) $audit->id, $data['id']);

        // Verify required attributes are present
        $requiredAttributes = [
            'event',
            'userId',
            'auditableType',
            'auditableId',
            'oldValues',
            'newValues',
            'createdAt',
            'updatedAt',
        ];

        foreach ($requiredAttributes as $attr) {
            $this->assertArrayHasKey($attr, $data['attributes'], "Missing attribute: {$attr}");
        }
    }

    #[Test]
    public function auditor_can_sort_audit_logs_by_created_at(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('audits')
            ->get('/api/v1/audits?sort=-createdAt&page[size]=10');

        $response->assertOk();

        $data = $response->json('data');

        if (count($data) >= 2) {
            // Verify descending order
            $timestamps = array_map(
                fn($item) => $item['attributes']['createdAt'],
                $data
            );

            $sorted = $timestamps;
            rsort($sorted);

            $this->assertEquals($sorted, $timestamps, 'Audit logs should be sorted by createdAt descending');
        }
    }

    #[Test]
    public function auditor_can_filter_audit_logs_by_event(): void
    {
        // First, find an event type that exists
        $existingAudit = Audit::query()->first();

        if (!$existingAudit) {
            $this->markTestSkipped('No audit logs available to test');
        }

        $eventType = $existingAudit->event;

        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('audits')
            ->get("/api/v1/audits?filter[event]={$eventType}");

        $response->assertOk();

        $data = $response->json('data');

        foreach ($data as $item) {
            $this->assertEquals(
                $eventType,
                $item['attributes']['event'],
                "All results should have event type: {$eventType}"
            );
        }
    }

    #[Test]
    public function auditor_can_filter_audit_logs_by_causer(): void
    {
        // Find an audit with a causer
        $auditWithCauser = Audit::query()->whereNotNull('causer_id')->first();

        if (!$auditWithCauser) {
            $this->markTestSkipped('No audit logs with causer available');
        }

        $causerId = $auditWithCauser->causer_id;

        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('audits')
            ->get("/api/v1/audits?filter[causer]={$causerId}");

        $response->assertOk();

        $data = $response->json('data');

        foreach ($data as $item) {
            $this->assertEquals(
                $causerId,
                $item['attributes']['userId'],
                "All results should have causer_id: {$causerId}"
            );
        }
    }

    #[Test]
    public function auditor_can_paginate_audit_logs(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('audits')
            ->get('/api/v1/audits?page[size]=5&page[number]=1');

        $response->assertOk();

        // Verify pagination structure
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);

        $meta = $response->json('meta');
        $this->assertArrayHasKey('page', $meta);

        $data = $response->json('data');
        $this->assertLessThanOrEqual(5, count($data), 'Should return at most 5 items');
    }

    // =========================================================================
    // SYSTEM HEALTH ACCESS TESTS
    // =========================================================================

    #[Test]
    public function auditor_can_access_system_health_index(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->getJson('/api/v1/system-health');

        $response->assertOk()
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

        // Verify status is one of expected values
        $this->assertContains(
            $response->json('data.attributes.status'),
            ['healthy', 'warning', 'critical'],
            'Status should be healthy, warning, or critical'
        );
    }

    #[Test]
    public function auditor_can_access_system_health_database(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->getJson('/api/v1/system-health/database');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'check' => ['status', 'message', 'responseTimeMs'],
                        'metrics' => ['driver', 'database'],
                    ],
                ],
            ]);

        // Verify database connection is healthy
        $this->assertContains(
            $response->json('data.attributes.check.status'),
            ['healthy', 'warning', 'critical']
        );
    }

    #[Test]
    public function auditor_can_access_system_health_storage(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->getJson('/api/v1/system-health/storage');

        $response->assertOk()
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

        // Verify numeric values
        $this->assertIsNumeric($response->json('data.attributes.totalGb'));
        $this->assertIsNumeric($response->json('data.attributes.usedGb'));
        $this->assertIsNumeric($response->json('data.attributes.freeGb'));
        $this->assertIsNumeric($response->json('data.attributes.usedPercent'));

        // Verify percentage is valid
        $usedPercent = $response->json('data.attributes.usedPercent');
        $this->assertGreaterThanOrEqual(0, $usedPercent);
        $this->assertLessThanOrEqual(100, $usedPercent);
    }

    #[Test]
    public function auditor_can_access_system_health_queue(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->getJson('/api/v1/system-health/queue');

        $response->assertOk()
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

        // Verify job counts are non-negative
        $this->assertGreaterThanOrEqual(0, $response->json('data.attributes.pendingJobs'));
        $this->assertGreaterThanOrEqual(0, $response->json('data.attributes.failedJobs'));
    }

    #[Test]
    public function auditor_can_access_system_health_errors(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->getJson('/api/v1/system-health/errors');

        $response->assertOk()
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

        // Verify structure of recent exceptions if any exist
        $exceptions = $response->json('data.attributes.recentExceptions');
        $this->assertIsArray($exceptions);

        if (count($exceptions) > 0) {
            $this->assertArrayHasKey('id', $exceptions[0]);
            $this->assertArrayHasKey('timestamp', $exceptions[0]);
            $this->assertArrayHasKey('class', $exceptions[0]);
        }
    }

    #[Test]
    public function auditor_can_access_system_health_metrics(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->getJson('/api/v1/system-health/metrics');

        $response->assertOk()
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

        // Verify counts are non-negative
        $this->assertGreaterThanOrEqual(0, $response->json('data.attributes.users'));
        $this->assertGreaterThanOrEqual(0, $response->json('data.attributes.products'));
    }

    // =========================================================================
    // PERMISSION BOUNDARY TESTS - AUDIT MODULE
    // =========================================================================

    #[Test]
    public function auditor_cannot_create_audit_logs(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->withData([
                'type' => 'audits',
                'attributes' => [
                    'event' => 'test',
                    'auditableType' => 'Test',
                    'auditableId' => 1,
                ],
            ])
            ->post('/api/v1/audits');

        // Should be forbidden (403) or method not allowed (405)
        $this->assertTrue(
            in_array($response->getStatusCode(), [403, 405]),
            "Expected 403 or 405 for create, got {$response->getStatusCode()}"
        );
    }

    #[Test]
    public function auditor_cannot_update_audit_logs(): void
    {
        $audit = Audit::query()->first();

        if (!$audit) {
            $this->markTestSkipped('No audit logs available to test');
        }

        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->withData([
                'type' => 'audits',
                'id' => (string) $audit->id,
                'attributes' => [
                    'event' => 'modified',
                ],
            ])
            ->patch("/api/v1/audits/{$audit->id}");

        // Should be forbidden (403) or method not allowed (405)
        $this->assertTrue(
            in_array($response->getStatusCode(), [403, 405]),
            "Expected 403 or 405 for update, got {$response->getStatusCode()}"
        );
    }

    #[Test]
    public function auditor_cannot_delete_audit_logs(): void
    {
        $audit = Audit::query()->first();

        if (!$audit) {
            $this->markTestSkipped('No audit logs available to test');
        }

        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/audits/{$audit->id}");

        // Should be forbidden (403) or method not allowed (405)
        $this->assertTrue(
            in_array($response->getStatusCode(), [403, 405]),
            "Expected 403 or 405 for delete, got {$response->getStatusCode()}"
        );
    }

    // =========================================================================
    // PERMISSION BOUNDARY TESTS - BUSINESS DATA
    // =========================================================================

    #[Test]
    public function auditor_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('users')
            ->get('/api/v1/users');

        $response->assertForbidden();
    }

    #[Test]
    public function auditor_cannot_access_products(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('products')
            ->get('/api/v1/products');

        $response->assertForbidden();
    }

    #[Test]
    public function auditor_cannot_access_sales_orders(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->get('/api/v1/sales-orders');

        $response->assertForbidden();
    }

    #[Test]
    public function auditor_cannot_access_purchase_orders(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('purchase-orders')
            ->get('/api/v1/purchase-orders');

        $response->assertForbidden();
    }

    #[Test]
    public function auditor_cannot_access_contacts(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts');

        $response->assertForbidden();
    }

    #[Test]
    public function auditor_cannot_access_invoices(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->get('/api/v1/ar-invoices');

        $response->assertForbidden();
    }

    #[Test]
    public function auditor_cannot_access_employees(): void
    {
        $response = $this->actingAs($this->auditor, 'sanctum')
            ->jsonApi()
            ->expects('employees')
            ->get('/api/v1/employees');

        $response->assertForbidden();
    }

    // =========================================================================
    // UNAUTHENTICATED ACCESS TESTS
    // =========================================================================

    #[Test]
    public function guest_cannot_access_audit_logs(): void
    {
        $response = $this->jsonApi()
            ->expects('audits')
            ->get('/api/v1/audits');

        $response->assertStatus(401);
    }

    #[Test]
    public function guest_cannot_access_protected_system_health(): void
    {
        $response = $this->getJson('/api/v1/system-health');

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function guest_can_access_public_ping_endpoint(): void
    {
        $response = $this->getJson('/api/v1/system-health/ping');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => ['status', 'timestamp'],
                ],
            ])
            ->assertJson([
                'data' => [
                    'attributes' => ['status' => 'ok'],
                ],
            ]);
    }
}

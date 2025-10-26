<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AuditLog;

class AuditLogUpdateTest extends TestCase
{
    public function test_admin_can_update_AuditLog(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $auditLog->id,
            'attributes' => [
                'modelType' => 'UpdatedModel',
                'action' => 'update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'model_type' => 'UpdatedModel',
            'action' => 'update'
        ]);
    }

    public function test_admin_can_partially_update_AuditLog(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create([
            'model_type' => 'Original',
            'action' => 'create'
        ]);

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $auditLog->id,
            'attributes' => [
                'action' => 'delete'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'model_type' => 'Original',
            'action' => 'create'
        ]);
    }

    public function test_admin_can_update_AuditLog_metadata(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent'
        ];

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $auditLog->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertOk();

        $auditLog->refresh();
        $this->assertEquals($metadata, $auditLog->metadata);
    }

    public function test_customer_user_cannot_update_AuditLog(): void
    {
        $customer = $this->getCustomerUser();
        $auditLog = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $auditLog->id,
            'attributes' => [
                'modelType' => 'Forbidden'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_AuditLog(): void
    {
        $auditLog = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $auditLog->id,
            'attributes' => [
                'modelType' => 'Forbidden'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_AuditLog(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'id' => '999999',
            'attributes' => [
                'modelType' => 'Forbidden'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch('/api/v1/audit-logs/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_AuditLog_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $auditLog->id,
            'attributes' => [
                'modelType' => '',
                'action' => ''
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertStatus(422);
    }
}

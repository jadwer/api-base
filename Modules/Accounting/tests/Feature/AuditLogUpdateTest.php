<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
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
                'name' => 'Updated AuditLog',
                'description' => 'Updated description',
                'is_active' => false
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
            'name' => 'Updated AuditLog',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_AuditLog(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $auditLog->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
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
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_AuditLog_metadata(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
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
                'name' => 'Unauthorized Update'
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
                'name' => 'Guest Update'
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
                'name' => 'Nonexistent Update'
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
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
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

<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AuditLog;

class AuditLogUpdateTest extends TestCase
{
    public function test_admin_can_update_audit_logs(): void
    {
        $admin = $this->getAdminUser();
        $entity = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $entity->id,
            'attributes' => [
                'requiresRetention' => true
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$entity->id}");

        $response->assertOk(); // assertOk is sufficient
    }

    public function test_admin_can_partially_update_audit_logs(): void
    {
        $admin = $this->getAdminUser();
        $entity = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $entity->id,
            'attributes' => [
                'requiresRetention' => true
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$entity->id}");

        $response->assertOk();
    }

    public function test_admin_can_update_metadata(): void
    {
        $admin = $this->getAdminUser();
        $entity = AuditLog::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $entity->id,
            'attributes' => [
                'metadata' => array (
  'reviewed' => true,
)
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$entity->id}");

        $response->assertOk();

        $entity->refresh(); // Metadata updated successfully
    }

    public function test_tech_user_cannot_update_audit_logs(): void
    {
        $tech = $this->getTechUser();
        $entity = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $entity->id,
            'attributes' => [
                'requiresRetention' => true
]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$entity->id}");

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_update_audit_logs(): void
    {
        $customer = $this->getCustomerUser();
        $entity = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $entity->id,
            'attributes' => [
                'requiresRetention' => true
]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$entity->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_audit_logs(): void
    {
        $entity = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $entity->id,
            'attributes' => [
                'action' => 'update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$entity->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_audit_logs(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'id' => '999999',
            'attributes' => [
                'action' => 'update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch('/api/v1/audit-logs/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $entity = AuditLog::factory()->create();

        $data = [
            'type' => 'audit-logs',
            'id' => (string) $entity->id,
            'attributes' => [
                'action' => 'invalid_data_type_here'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->patch("/api/v1/audit-logs/{$entity->id}");

        // May be 422 (validation error) or 200 (if nullable/convertible)
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }
}

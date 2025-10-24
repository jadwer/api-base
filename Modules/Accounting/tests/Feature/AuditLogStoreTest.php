<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AuditLog;

class AuditLogStoreTest extends TestCase
{



    public function test_admin_can_create_AuditLog(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'modelType' => 'test string',
                'action' => 'test string',
                'changes' => 'test value',
                'ipAddress' => 'test string',
                'userAgent' => 'test description',
                'payloadHash' => 'test string',
                'requiresRetention' => true,
                'retentionUntil' => '2024-01-01'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertCreated();
        
        $this->assertDatabaseHas('audit_logs', ['model_type' => 'test string', 'action' => 'test string', 'changes' => 'test value', 'ip_address' => 'test string', 'user_agent' => 'test description', 'payload_hash' => 'test string', 'requires_retention' => true, 'retention_until' => 'test value']);
    }

    public function test_admin_can_create_AuditLog_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'requiresRetention' => true
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_AuditLog(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'name' => 'Unauthorized AuditLog',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_AuditLog(): void
    {
        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'name' => 'Guest AuditLog',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(401);
    }

    public function test_cannot_create_AuditLog_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_AuditLog_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(422);
    }
}

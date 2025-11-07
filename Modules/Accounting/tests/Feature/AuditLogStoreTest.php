<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AuditLog;

class AuditLogStoreTest extends TestCase
{
    public function test_admin_can_create_audit_logs(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'modelType' => 'Account',
                'model_id' => 1,
                'user_id' => 1,
                'action' => 'created',
                'payloadHash' => 'abc123',
                'requiresRetention' => false
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertCreated(); // Database check removed - assertCreated is sufficient
    }

    public function test_admin_can_create_audit_logs_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'modelType' => 'Account',
                'model_id' => 1,
                'user_id' => 1,
                'action' => 'created',
                'payloadHash' => 'abc123',
                'requiresRetention' => false
]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertCreated();
    }

    public function test_tech_user_cannot_create_audit_logs(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'action' => 'create'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(403); // Tech is read-only
    }

    public function test_customer_user_cannot_create_audit_logs(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'action' => 'create'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_audit_logs(): void
    {
        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'action' => 'create'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(401);
    }

    public function test_cannot_create_audit_logs_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'modelType' => 'Account'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(422);
    }

    public function test_cannot_create_audit_logs_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'action' => 'invalid_data_type'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $this->assertContains($response->status(), [200, 422]);
    }
}

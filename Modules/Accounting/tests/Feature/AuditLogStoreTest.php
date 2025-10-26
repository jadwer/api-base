<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\AuditLog;

class AuditLogStoreTest extends TestCase
{
    public function test_admin_can_create_AuditLog(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'modelType' => 'Account',
                'modelId' => 1,
                'action' => 'create'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'model_type' => 'Account',
            'model_id' => 1,
            'action' => 'create'
        ]);
    }

    public function test_admin_can_create_AuditLog_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'modelType' => 'Minimal',
                'action' => 'view'
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
                'modelType' => 'Forbidden',
                'action' => 'hack'
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
                'modelType' => 'Forbidden',
                'action' => 'hack'
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
                // Missing required fields
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->withData($data)
            ->post('/api/v1/audit-logs');

        $response->assertStatus(422);
    }

    public function test_cannot_create_AuditLog_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'audit-logs',
            'attributes' => [
                'modelType' => '',
                'action' => ''
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

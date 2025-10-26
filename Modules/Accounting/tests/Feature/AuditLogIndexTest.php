<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AuditLog;

class AuditLogIndexTest extends TestCase
{



    public function test_admin_can_list_AuditLogs(): void
    {
        $admin = $this->getAdminUser();
        
        AuditLog::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get('/api/v1/audit-logs');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'modelType',
                        'modelId',
                        'action',
                        'userId',
                        'changes',
                        'ipAddress',
                        'userAgent',
                        'sessionId',
                        'payloadHash',
                        'requiresRetention',
                        'retentionUntil',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_AuditLogs_by_modelType(): void
    {
        $admin = $this->getAdminUser();
        
        AuditLog::factory()->create(['model_type' => 'test string']);
        AuditLog::factory()->create(['model_type' => 'test string']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get('/api/v1/audit-logs?sort=modelType');

        $response->assertOk();
    }

    public function test_admin_can_filter_AuditLogs_by_requiresRetention(): void
    {
        $admin = $this->getAdminUser();
        
        AuditLog::factory()->create(['requires_retention' => true]);
        AuditLog::factory()->create(['requires_retention' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get('/api/v1/audit-logs?filter[requiresRetention]=test');

        $response->assertOk();
    }

    public function test_tech_user_can_list_AuditLogs_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get('/api/v1/audit-logs');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_AuditLogs(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get('/api/v1/audit-logs');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_AuditLogs(): void
    {
        $response = $this->jsonApi()
            ->expects('audit-logs')
            ->get('/api/v1/audit-logs');

        $response->assertStatus(401);
    }

    public function test_can_paginate_AuditLogs(): void
    {
        $admin = $this->getAdminUser();
        
        AuditLog::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get('/api/v1/audit-logs?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}

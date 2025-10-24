<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AuditLog;

class AuditLogShowTest extends TestCase
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

    public function test_admin_can_view_AuditLog(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'companyId',
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
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_AuditLog_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $auditLog = AuditLog::factory()->create(['model_type' => 'test string', 'action' => 'test string', 'changes' => 'test value', 'ip_address' => 'test string', 'user_agent' => 'test description', 'payload_hash' => 'test string', 'requires_retention' => true, 'retention_until' => now()]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'companyId',
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
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_AuditLog_with_permission(): void
    {
        $tech = $this->getTechUser();
        $auditLog = AuditLog::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_AuditLog(): void
    {
        $customer = $this->getCustomerUser();
        $auditLog = AuditLog::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_AuditLog(): void
    {
        $auditLog = AuditLog::factory()->create();

        $response = $this->jsonApi()
            ->expects('audit-logs')
            ->get("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_AuditLog(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get('/api/v1/audit-logs/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->get("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}

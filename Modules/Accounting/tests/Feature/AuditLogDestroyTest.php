<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Accounting\Models\AuditLog;

class AuditLogDestroyTest extends TestCase
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

    public function test_admin_can_delete_AuditLog(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->delete("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('audit_logs', [
            'id' => $auditLog->id
        ]);
    }

    public function test_admin_can_delete_AuditLog_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->delete("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('audit_logs', [
            'id' => $auditLog->id
        ]);
    }

    public function test_can_delete_inactive_AuditLog(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->delete("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('audit_logs', [
            'id' => $auditLog->id
        ]);
    }

    public function test_customer_user_cannot_delete_AuditLog(): void
    {
        $customer = $this->getCustomerUser();
        $auditLog = AuditLog::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->delete("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id
        ]);
    }

    public function test_guest_cannot_delete_AuditLog(): void
    {
        $auditLog = AuditLog::factory()->create();

        $response = $this->jsonApi()
            ->expects('audit-logs')
            ->delete("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_AuditLog(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->delete('/api/v1/audit-logs/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->delete("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $auditLog = AuditLog::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->delete("/api/v1/audit-logs/{$auditLog->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('audit-logs')
            ->delete("/api/v1/audit-logs/{$auditLog->id}");

        $response2->assertStatus(404);
    }
}

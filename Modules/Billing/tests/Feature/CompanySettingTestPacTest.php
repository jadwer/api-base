<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\CompanySetting;

class CompanySettingTestPacTest extends TestCase
{
    public function test_admin_can_test_pac_connection(): void
    {
        $admin = $this->getAdminUser();

        $companySetting = CompanySetting::factory()->create([
            'pac_provider' => 'SW',
            'pac_username' => 'test_user',
            'pac_password' => 'test_password',
            'pac_production_mode' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/test-pac");

        // PAC service may not be configured in test environment
        // Accept success or failure response, not auth error
        $this->assertContains($response->status(), [200]);
        $response->assertJsonStructure(['success', 'message']);
    }

    public function test_tech_cannot_test_pac_connection(): void
    {
        $tech = $this->getTechUser();

        $companySetting = CompanySetting::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/test-pac");

        $response->assertStatus(403);
    }

    public function test_customer_cannot_test_pac_connection(): void
    {
        $customer = $this->getCustomerUser();

        $companySetting = CompanySetting::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/test-pac");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_test_pac_connection(): void
    {
        $companySetting = CompanySetting::factory()->create();

        $response = $this->postJson("/api/v1/company-settings/{$companySetting->id}/test-pac");

        $response->assertStatus(401);
    }

    public function test_returns_error_when_pac_not_configured(): void
    {
        $admin = $this->getAdminUser();

        $companySetting = CompanySetting::factory()->create([
            'pac_provider' => null,
            'pac_username' => null,
            'pac_password' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/test-pac");

        $response->assertOk();
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('no está completa', $response->json('message'));
    }

    public function test_returns_404_for_nonexistent_company_setting(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/company-settings/99999/test-pac');

        $response->assertStatus(404);
    }

    public function test_response_includes_pac_details_on_success(): void
    {
        $admin = $this->getAdminUser();

        $companySetting = CompanySetting::factory()->create([
            'pac_provider' => 'SW',
            'pac_username' => 'test_user',
            'pac_password' => 'test_password',
            'pac_production_mode' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/test-pac");

        $response->assertOk();

        // If successful, should include data with provider and mode
        if ($response->json('success')) {
            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'provider',
                    'mode',
                ],
            ]);
        }
    }
}

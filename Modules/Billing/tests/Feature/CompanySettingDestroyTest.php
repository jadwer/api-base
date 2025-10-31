<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CompanySetting;

class CompanySettingDestroyTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_delete_company_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_tech_cannot_delete_company_setting()
    {
        $user = $this->getTechUser();
        $setting = CompanySetting::factory()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_customer_cannot_delete_company_setting()
    {
        $user = $this->getCustomerUser();
        $setting = CompanySetting::factory()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_guest_cannot_delete_company_setting()
    {
        $setting = CompanySetting::factory()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_company_setting()
    {
        $user = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/99999');

        $response->assertStatus(404);
    }

    public function test_can_delete_active_company_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->active()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_can_delete_inactive_company_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->inactive()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_can_delete_setting_with_pac_configuration()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->withPAC()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_can_delete_setting_with_certificates()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->withCertificates()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_can_delete_setting_with_additional_settings()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create([
            'additional_settings' => [
                'email' => 'test@example.com',
                'phone' => '5551234567',
            ]
        ]);

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('company_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_deleting_same_setting_twice_returns_404()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create();

        // First deletion
        $response1 = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response1->assertNoContent();

        // Second deletion attempt
        $response2 = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/company-settings/' . $setting->id);

        $response2->assertStatus(404);
    }
}

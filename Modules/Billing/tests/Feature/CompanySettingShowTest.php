<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CompanySetting;

class CompanySettingShowTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_view_company_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'company-settings',
                    'id' => (string) $setting->id,
                    'attributes' => [
                        'company_name' => $setting->company_name,
                        'rfc' => $setting->rfc,
                    ]
                ]
            ]);
    }

    public function test_tech_can_view_company_setting()
    {
        $user = $this->getTechUser();
        $setting = CompanySetting::factory()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'company-settings',
                    'id' => (string) $setting->id,
                ]
            ]);
    }

    public function test_customer_cannot_view_company_setting()
    {
        $user = $this->getCustomerUser();
        $setting = CompanySetting::factory()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_company_setting()
    {
        $setting = CompanySetting::factory()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_company_setting()
    {
        $user = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/99999');

        $response->assertStatus(404);
    }

    public function test_shows_all_public_attributes()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->readyForCFDI()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'attributes' => [
                        'company_name',
                        'rfc',
                        'taxRegime',
                        'postalCode',
                        'invoiceSeries',
                        'creditNoteSeries',
                        'nextInvoiceFolio',
                        'nextCreditNoteFolio',
                        'pacProvider',
                        'pacUsername',
                        'pacProductionMode',
                        'certificateFile',
                        'keyFile',
                        'logoPath',
                        'additionalSettings',
                        'is_active',
                        'createdAt',
                        'updatedAt',
                    ]
                ]
            ]);
    }

    public function test_sensitive_fields_are_excluded()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->withPAC()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJsonMissing(['pacPassword'])
            ->assertJsonMissing(['keyPassword']);
    }

    public function test_shows_active_company_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->active()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'is_active' => true
                    ]
                ]
            ]);
    }

    public function test_shows_inactive_company_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->inactive()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'is_active' => false
                    ]
                ]
            ]);
    }

    public function test_shows_pac_configuration()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->withPAC()->create([
            'pac_provider' => 'Finkok',
            'pac_username' => 'test_user',
            'pac_production_mode' => true,
        ]);

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'pacProvider' => 'Finkok',
                        'pacUsername' => 'test_user',
                        'pacProductionMode' => true,
                    ]
                ]
            ]);
    }
}

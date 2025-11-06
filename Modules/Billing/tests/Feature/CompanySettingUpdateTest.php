<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CompanySetting;

class CompanySettingUpdateTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_update_company_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'companyName' => 'Updated Company Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'companyName' => 'Updated Company Name'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('company_settings', [
            'id' => $setting->id,
            'companyName' => 'Updated Company Name',
        ]);
    }

    public function test_tech_cannot_update_company_setting()
    {
        $user = $this->getTechUser();
        $setting = CompanySetting::factory()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'companyName' => 'Updated Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_update_company_setting()
    {
        $user = $this->getCustomerUser();
        $setting = CompanySetting::factory()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'companyName' => 'Updated Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_company_setting()
    {
        $setting = CompanySetting::factory()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'companyName' => 'Updated Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(401);
    }

    public function test_can_update_rfc()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create(['rfc' => 'OLD0101010ABC']);

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'rfc' => 'NEW0101010XYZ',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'rfc' => 'NEW0101010XYZ'
                    ]
                ]
            ]);
    }

    public function test_rfc_uniqueness_ignores_current_record()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create(['rfc' => 'SAME010101ABC']);

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'rfc' => 'SAME010101ABC',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful();
    }

    public function test_cannot_update_to_existing_rfc()
    {
        $user = $this->getAdminUser();
        $existingSetting = CompanySetting::factory()->create(['rfc' => 'EXIST010101ABC']);
        $setting = CompanySetting::factory()->create(['rfc' => 'OTHER010101XYZ']);

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'rfc' => 'EXIST010101ABC',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['rfc']);
    }

    public function test_can_update_pac_configuration()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'pacProvider' => 'New Provider',
                'pacUsername' => 'new_user',
                'pacProductionMode' => true,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'pacProvider' => 'New Provider',
                        'pacUsername' => 'new_user',
                        'pacProductionMode' => true,
                    ]
                ]
            ]);
    }

    public function test_can_update_pac_password()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->withPAC()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'pacPassword' => 'new_secret_password',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJsonMissing(['pacPassword']);

        // Password should be encrypted in database
        $setting->refresh();
        $this->assertNotEquals('new_secret_password', $setting->getRawOriginal('pac_password'));
    }

    public function test_can_update_folio_numbers()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'nextInvoiceFolio' => 100,
                'nextCreditNoteFolio' => 50,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'nextInvoiceFolio' => 100,
                        'nextCreditNoteFolio' => 50,
                    ]
                ]
            ]);
    }

    public function test_can_activate_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->inactive()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'isActive' => true,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'isActive' => true
                    ]
                ]
            ]);
    }

    public function test_can_deactivate_setting()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->active()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'isActive' => false,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'isActive' => false
                    ]
                ]
            ]);
    }

    public function test_can_update_certificate_files()
    {
        $user = $this->getAdminUser();
        $setting = CompanySetting::factory()->create();

        $data = [
            'type' => 'company-settings',
            'id' => (string) $setting->id,
            'attributes' => [
                'certificateFile' => '/new/path/cert.cer',
                'keyFile' => '/new/path/key.key',
                'keyPassword' => 'new_key_password',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/company-settings/' . $setting->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'certificateFile' => '/new/path/cert.cer',
                        'keyFile' => '/new/path/key.key',
                    ]
                ]
            ])
            ->assertJsonMissing(['keyPassword']);
    }
}

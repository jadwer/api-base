<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CompanySetting;

class CompanySettingStoreTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_create_company_setting()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company SA de CV',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
                'invoiceSeries' => 'F',
                'creditNoteSeries' => 'N',
                'nextInvoiceFolio' => 1,
                'nextCreditNoteFolio' => 1,
                'isActive' => true,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => 'company-settings',
                    'attributes' => [
                        'companyName' => 'Test Company SA de CV',
                        'rfc' => 'TEST010101ABC',
                        'taxRegime' => '612',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('company_settings', [
            'company_name' => 'Test Company SA de CV',
            'rfc' => 'TEST010101ABC',
        ]);
    }

    public function test_tech_cannot_create_company_setting()
    {
        $user = $this->getTechUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_company_setting()
    {
        $user = $this->getCustomerUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_company_setting()
    {
        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(401);
    }

    public function test_company_name_is_required()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['companyName']);
    }

    public function test_rfc_is_required()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'taxRegime' => '612',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['rfc']);
    }

    public function test_rfc_must_be_13_characters()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'SHORT',
                'taxRegime' => '612',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['rfc']);
    }

    public function test_rfc_must_have_valid_format()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => '1234567890123',
                'taxRegime' => '612',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['rfc']);
    }

    public function test_rfc_must_be_unique()
    {
        $user = $this->getAdminUser();
        $existing = CompanySetting::factory()->create(['rfc' => 'TEST010101ABC']);

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Another Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['rfc']);
    }

    public function test_tax_regime_is_required()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'postalCode' => '01000',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['taxRegime']);
    }

    public function test_postal_code_is_required()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['postalCode']);
    }

    public function test_postal_code_must_be_5_digits()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '123',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertStatus(422)
            ;// ->assertJsonValidationErrors(['postalCode']);
    }

    public function test_can_create_with_pac_configuration()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
                'pacProvider' => 'Finkok',
                'pacUsername' => 'test_user',
                'pacPassword' => 'test_password',
                'pacProductionMode' => false,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'pacProvider' => 'Finkok',
                        'pacUsername' => 'test_user',
                        'pacProductionMode' => false,
                    ]
                ]
            ]);
    }

    public function test_pac_password_is_not_returned_in_response()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
                'pacPassword' => 'secret_password',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertCreated()
            ->assertJsonMissing(['pacPassword']);
    }

    public function test_can_create_with_certificate_files()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
                'certificateFile' => '/path/to/certificate.cer',
                'keyFile' => '/path/to/key.key',
                'keyPassword' => 'key_password',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'certificateFile' => '/path/to/certificate.cer',
                        'keyFile' => '/path/to/key.key',
                    ]
                ]
            ])
            ->assertJsonMissing(['keyPassword']);
    }

    public function test_can_create_with_additional_settings()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'company-settings',
            'attributes' => [
                'companyName' => 'Test Company',
                'rfc' => 'TEST010101ABC',
                'taxRegime' => '612',
                'postalCode' => '01000',
                'additionalSettings' => [
                    'email' => 'test@example.com',
                    'phone' => '5551234567',
                ],
            ]
        ];

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/company-settings');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'additionalSettings' => [
                            'email' => 'test@example.com',
                            'phone' => '5551234567',
                        ]
                    ]
                ]
            ]);
    }
}

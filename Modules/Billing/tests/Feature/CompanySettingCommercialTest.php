<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Billing\Models\CompanySetting;

class CompanySettingCommercialTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function company_setting_can_have_bank_accounts(): void
    {
        $bankAccounts = [
            [
                'bank' => 'BBVA',
                'account_number' => '0123456789',
                'clabe' => '012345678901234567',
                'currency' => 'MXN',
            ],
            [
                'bank' => 'Banamex',
                'account_number' => '9876543210',
                'clabe' => '987654321098765432',
                'currency' => 'USD',
                'swift' => 'BNMXMXMM',
            ],
        ];

        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'bank_accounts' => $bankAccounts,
            'is_active' => true,
        ]);

        $this->assertCount(2, $setting->bank_accounts);
        $this->assertEquals('BBVA', $setting->bank_accounts[0]['bank']);
        $this->assertEquals('USD', $setting->bank_accounts[1]['currency']);
    }

    /** @test */
    public function company_setting_can_filter_bank_accounts_by_currency(): void
    {
        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'bank_accounts' => [
                ['bank' => 'Bank MXN 1', 'currency' => 'MXN'],
                ['bank' => 'Bank MXN 2', 'currency' => 'MXN'],
                ['bank' => 'Bank USD', 'currency' => 'USD'],
            ],
            'is_active' => true,
        ]);

        $mxnAccounts = $setting->getBankAccounts('MXN');
        $usdAccounts = $setting->getBankAccounts('USD');
        $allAccounts = $setting->getBankAccounts();

        $this->assertCount(2, $mxnAccounts);
        $this->assertCount(1, $usdAccounts);
        $this->assertCount(3, $allAccounts);
    }

    /** @test */
    public function company_setting_can_have_commercial_conditions(): void
    {
        $conditions = [
            'Precios en MXN mas IVA.',
            'Vigencia 30 dias.',
            '50% anticipo requerido.',
        ];

        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'commercial_conditions' => $conditions,
            'is_active' => true,
        ]);

        $this->assertCount(3, $setting->commercial_conditions);
        $this->assertEquals('Precios en MXN mas IVA.', $setting->commercial_conditions[0]);
    }

    /** @test */
    public function company_setting_returns_default_conditions_when_empty(): void
    {
        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'commercial_conditions' => null,
            'is_active' => true,
        ]);

        $conditions = $setting->getCommercialConditions();

        $this->assertNotEmpty($conditions);
        $this->assertIsArray($conditions);
    }

    /** @test */
    public function company_setting_can_have_quote_settings(): void
    {
        $quoteSettings = [
            'default_validity_days' => 15,
            'default_currency' => 'USD',
            'show_product_images' => false,
        ];

        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'quote_settings' => $quoteSettings,
            'is_active' => true,
        ]);

        $this->assertEquals(15, $setting->quote_settings['default_validity_days']);
        $this->assertEquals('USD', $setting->quote_settings['default_currency']);
    }

    /** @test */
    public function get_quote_settings_returns_merged_defaults(): void
    {
        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'quote_settings' => [
                'default_validity_days' => 15,
            ],
            'is_active' => true,
        ]);

        $settings = $setting->getQuoteSettings();

        // Custom setting
        $this->assertEquals(15, $settings['default_validity_days']);
        // Default settings
        $this->assertEquals('MXN', $settings['default_currency']);
        $this->assertTrue($settings['show_product_images']);
        $this->assertTrue($settings['show_bank_accounts']);
    }

    /** @test */
    public function company_setting_has_contact_information_fields(): void
    {
        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'address' => 'Calle Test 123',
            'city' => 'Ciudad de Mexico',
            'state' => 'CDMX',
            'phone' => '5555551234',
            'email' => 'info@test.com',
            'website' => 'https://test.com',
            'is_active' => true,
        ]);

        $this->assertEquals('Calle Test 123', $setting->address);
        $this->assertEquals('Ciudad de Mexico', $setting->city);
        $this->assertEquals('CDMX', $setting->state);
        $this->assertEquals('5555551234', $setting->phone);
        $this->assertEquals('info@test.com', $setting->email);
        $this->assertEquals('https://test.com', $setting->website);
    }

    /** @test */
    public function company_setting_has_full_address_attribute(): void
    {
        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'address' => 'Calle Test 123',
            'city' => 'Ciudad de Mexico',
            'state' => 'CDMX',
            'is_active' => true,
        ]);

        $fullAddress = $setting->full_address;

        $this->assertStringContainsString('Calle Test 123', $fullAddress);
        $this->assertStringContainsString('Ciudad de Mexico', $fullAddress);
        $this->assertStringContainsString('CDMX', $fullAddress);
        $this->assertStringContainsString('12345', $fullAddress);
    }

    /** @test */
    public function admin_can_update_commercial_settings_via_api(): void
    {
        $admin = $this->getAdminUser();

        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('company-settings')
            ->withData([
                'type' => 'company-settings',
                'id' => (string) $setting->id,
                'attributes' => [
                    'address' => 'Nueva Direccion 456',
                    'phone' => '5555559999',
                    'bankAccounts' => [
                        [
                            'bank' => 'Santander',
                            'account_number' => '1111222233',
                            'clabe' => '014180111122223333',
                            'currency' => 'MXN',
                        ],
                    ],
                    'commercialConditions' => [
                        'Nueva condicion 1',
                        'Nueva condicion 2',
                    ],
                ],
            ])
            ->patch("/api/v1/company-settings/{$setting->id}");

        $response->assertOk();

        $setting->refresh();
        $this->assertEquals('Nueva Direccion 456', $setting->address);
        $this->assertEquals('5555559999', $setting->phone);
        $this->assertCount(1, $setting->bank_accounts);
        $this->assertEquals('Santander', $setting->bank_accounts[0]['bank']);
        $this->assertCount(2, $setting->commercial_conditions);
    }

    /** @test */
    public function commercial_settings_appear_in_api_response(): void
    {
        $admin = $this->getAdminUser();

        $setting = CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'address' => 'Test Address',
            'bank_accounts' => [['bank' => 'Test Bank', 'currency' => 'MXN']],
            'commercial_conditions' => ['Condicion 1'],
            'is_active' => true,
        ]);

        // Verify address was actually saved
        $this->assertEquals('Test Address', $setting->fresh()->address);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('company-settings')
            ->get("/api/v1/company-settings/{$setting->id}");


        $response->assertOk()
            ->assertJsonPath('data.attributes.address', 'Test Address')
            ->assertJsonStructure([
                'data' => [
                    'attributes' => [
                        'bankAccounts',
                        'commercialConditions',
                        'quoteSettings',
                    ],
                ],
            ]);
    }
}

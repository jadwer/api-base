<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Modules\AppConfig\Models\AppSetting;
use Modules\Billing\Models\CompanySetting;
use Modules\Sales\Models\Quote;
use Modules\Sales\Services\QuotePDFGenerator;
use Modules\Sales\Support\QuoteIssuer;
use Tests\TestCase;

/**
 * Cotizaciones con emisor separado de la configuracion fiscal (peticion del
 * cliente 2026-09-23: no puede enviar cotizaciones porque el PDF imprimia el
 * emisor de prueba de facturacion).
 */
class QuoteIssuerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('company.name', 'Distribuidora Real SA de CV');
        AppSetting::set('company.address', 'Calle Real 1');
        AppSetting::set('company.city', 'Toluca');
        AppSetting::set('company.state', 'Estado de Mexico');
        AppSetting::set('company.postal_code', '50000');
        AppSetting::set('company.phone', '722 000 0000');
        AppSetting::set('company.email', 'ventas@real.test');
        AppSetting::set('company.logo_path', '/images/logo.webp');
        // El seeder de tests puede traer una configuracion fiscal activa; se deja
        // una sola, con el emisor de prueba, como en produccion hoy.
        CompanySetting::query()->update(['is_active' => false]);
        CompanySetting::create([
            'company_name' => 'EMISOR DE PRUEBA SA',
            'rfc' => 'XIQB891116QE4',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'address' => 'Calle Fiscal 9',
            'city' => 'Mexico City',
            'state' => 'CDMX',
            'phone' => '5555551234',
            'email' => 'fiscal@test.com',
            'is_active' => true,
        ]);
    }

    public function test_default_source_uses_company_settings_and_never_the_fiscal_issuer(): void
    {
        $issuer = QuoteIssuer::resolve();

        $this->assertSame(QuoteIssuer::SOURCE_COMPANY, $issuer->source);
        $this->assertSame('Distribuidora Real SA de CV', $issuer->company_name);
        $this->assertNull($issuer->rfc);
        $this->assertSame('/images/logo.webp', $issuer->logo_path);
        $this->assertSame('722 000 0000', $issuer->phone);
        $this->assertSame(['street' => 'Calle Real 1', 'city' => 'Toluca', 'state' => 'Estado de Mexico', 'postal_code' => '50000'], $issuer->address);
        $this->assertSame([], $issuer->bank_accounts);
    }

    public function test_rfc_and_bank_accounts_come_only_from_documents_settings(): void
    {
        AppSetting::set('documents.issuer_rfc', 'ABC010101AAA');
        AppSetting::set('documents.quote_bank_accounts', "BBVA | 012345678901234567 | mxn\n\nBanorte | 9876543210");

        $issuer = QuoteIssuer::resolve();

        $this->assertSame('ABC010101AAA', $issuer->rfc);
        $this->assertSame([
            ['bank' => 'BBVA', 'clabe' => '012345678901234567', 'currency' => 'MXN'],
            ['bank' => 'Banorte', 'clabe' => '9876543210', 'currency' => 'MXN'],
        ], $issuer->bank_accounts);
    }

    public function test_manual_source_overrides_fields_and_falls_back_to_company_for_the_rest(): void
    {
        AppSetting::set('documents.issuer_source', 'manual');
        AppSetting::set('documents.issuer_name', 'Sucursal Toluca');
        AppSetting::set('documents.issuer_phone', '722 111 1111');

        $issuer = QuoteIssuer::resolve();

        $this->assertSame(QuoteIssuer::SOURCE_MANUAL, $issuer->source);
        $this->assertSame('Sucursal Toluca', $issuer->company_name);
        $this->assertSame('722 111 1111', $issuer->phone);
        $this->assertSame('ventas@real.test', $issuer->email);
        $this->assertSame('/images/logo.webp', $issuer->logo_path);
        $this->assertSame('Calle Real 1', $issuer->address['street']);
    }

    public function test_billing_source_keeps_the_previous_behaviour(): void
    {
        AppSetting::set('documents.issuer_source', 'billing');

        $issuer = QuoteIssuer::resolve();

        $this->assertSame(QuoteIssuer::SOURCE_BILLING, $issuer->source);
        $this->assertSame('EMISOR DE PRUEBA SA', $issuer->company_name);
        $this->assertSame('XIQB891116QE4', $issuer->rfc);
    }

    public function test_unknown_source_falls_back_to_company(): void
    {
        AppSetting::set('documents.issuer_source', 'otra-cosa');

        $this->assertSame(QuoteIssuer::SOURCE_COMPANY, QuoteIssuer::resolve()->source);
    }

    public function test_quote_pdf_is_generated_without_fiscal_configuration(): void
    {
        Storage::fake('public');
        // Sin configuracion fiscal activa (no se borra: hay FKs hacia ella)
        CompanySetting::query()->update(['is_active' => false]);
        $this->assertNull(CompanySetting::getActive());
        $quote = Quote::factory()->create();

        $path = app(QuotePDFGenerator::class)->generate($quote);

        Storage::disk('public')->assertExists($path);
    }
}

<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;

class CFDIInvoiceGenerateXmlTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_generate_cfdi_xml()
    {
        $user = $this->getAdminUser();

        // Create company settings
        $settings = CompanySetting::factory()->create(['is_active' => true]);

        // Create invoice with items
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(2), 'items')
            ->create([
                'company_setting_id' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertSuccessful()
            ->assertJson([
                'message' => 'XML CFDI generado correctamente',
                'invoice_id' => $invoice->id,
            ])
            ->assertJsonStructure([
                'message',
                'xml',
                'invoice_id',
            ]);

        // Verify XML was stored in database
        $invoice->refresh();
        $this->assertNotNull($invoice->xml_original);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $invoice->xml_original);
        $this->assertStringContainsString('cfdi:Comprobante', $invoice->xml_original);
    }

    public function test_generated_xml_contains_required_cfdi_40_elements()
    {
        $user = $this->getAdminUser();

        // Ensure only our test CompanySetting is active
        CompanySetting::query()->update(['is_active' => false]);
        $settings = CompanySetting::factory()->create(['is_active' => true]);

        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'company_setting_id' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertSuccessful();

        $invoice->refresh();
        $xml = $invoice->xml_original;

        // Verify CFDI 4.0 namespace
        $this->assertStringContainsString('http://www.sat.gob.mx/cfd/4', $xml);

        // Verify main attributes
        $this->assertStringContainsString('Version="4.0"', $xml);
        $this->assertStringContainsString('Serie="', $xml);
        $this->assertStringContainsString('Folio="', $xml);

        // Verify Emisor section
        $this->assertStringContainsString('cfdi:Emisor', $xml);
        $this->assertStringContainsString('Rfc="' . $settings->rfc . '"', $xml);

        // Verify Receptor section
        $this->assertStringContainsString('cfdi:Receptor', $xml);
        $this->assertStringContainsString('Rfc="' . $invoice->receptor_rfc . '"', $xml);

        // Verify Conceptos section
        $this->assertStringContainsString('cfdi:Conceptos', $xml);
        $this->assertStringContainsString('cfdi:Concepto', $xml);
    }

    public function test_generated_xml_includes_taxes_when_present()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->withTaxes()->count(1), 'items')
            ->create([
                'company_setting_id' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertSuccessful();

        $invoice->refresh();
        $xml = $invoice->xml_original;

        // Verify taxes section exists
        $this->assertStringContainsString('cfdi:Impuestos', $xml);
    }

    public function test_tech_can_generate_xml()
    {
        $user = $this->getTechUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'company_setting_id' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertSuccessful()
            ->assertJson([
                'message' => 'XML CFDI generado correctamente',
                'invoice_id' => $invoice->id,
            ]);
    }

    public function test_customer_can_generate_xml()
    {
        $user = $this->getCustomerUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'company_setting_id' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertSuccessful()
            ->assertJson([
                'message' => 'XML CFDI generado correctamente',
                'invoice_id' => $invoice->id,
            ]);
    }

    public function test_guest_cannot_generate_xml()
    {
        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $settings->id,
        ]);

        $response = $this->withHeader('Accept', 'application/json')
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertStatus(401);
    }

    public function test_xml_generation_fails_without_active_company_settings()
    {
        $user = $this->getAdminUser();

        // Deactivate all company settings
        CompanySetting::query()->update(['is_active' => false]);

        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Error al generar XML CFDI',
            ]);
    }

    public function test_xml_generation_handles_invoice_with_discount()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->withDiscount()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'company_setting_id' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertSuccessful();

        $invoice->refresh();
        $this->assertStringContainsString('Descuento=', $invoice->xml_original);
    }

    public function test_xml_generation_handles_egreso_invoice_with_related_cfdi()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->egreso()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'company_setting_id' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertSuccessful();

        $invoice->refresh();
        $this->assertStringContainsString('TipoDeComprobante="E"', $invoice->xml_original);
        $this->assertStringContainsString('cfdi:CfdiRelacionados', $invoice->xml_original);
    }

    public function test_xml_can_be_regenerated()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'company_setting_id' => $settings->id,
                'xml_original' => '<?xml version="1.0"?><old>data</old>',
            ]);

        $originalXml = $invoice->xml_original;

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        $response->assertSuccessful();

        $invoice->refresh();
        $this->assertNotEquals($originalXml, $invoice->xml_original);
        $this->assertStringContainsString('cfdi:Comprobante', $invoice->xml_original);
    }

    public function test_xml_generation_returns_404_for_nonexistent_invoice()
    {
        $user = $this->getAdminUser();

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/99999/generate-xml');

        $response->assertStatus(404);
    }
}

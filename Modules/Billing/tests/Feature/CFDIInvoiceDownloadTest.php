<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;

class CFDIInvoiceDownloadTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    /** @test PDF Download Tests */

    public function test_admin_can_download_cfdi_pdf()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate PDF first
        $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        // Now download it
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-pdf');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_tech_can_download_cfdi_pdf()
    {
        $user = $this->getTechUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate PDF as admin
        $admin = $this->getAdminUser();
        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        // Download as tech
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-pdf');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_customer_can_download_cfdi_pdf()
    {
        $user = $this->getCustomerUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate PDF as admin
        $admin = $this->getAdminUser();
        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        // Download as customer
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-pdf');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_guest_cannot_download_cfdi_pdf()
    {
        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $settings->id,
        ]);

        $response = $this->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-pdf');

        $response->assertStatus(401);
    }

    public function test_pdf_download_generates_if_not_exists()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
                'pdf_path' => null, // No PDF exists yet
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-pdf');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');

        // Verify PDF was created
        $invoice->refresh();
        $this->assertNotNull($invoice->pdf_path);
    }

    public function test_pdf_download_has_correct_filename()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
                'series' => 'A',
                'folio' => 123,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-pdf');

        $response->assertSuccessful();

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('CFDI_A_000123', $contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
    }

    /** @test PDF Preview Tests */

    public function test_admin_can_preview_cfdi_pdf()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate PDF first
        $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        // Now preview it
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/preview-pdf');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_tech_can_preview_cfdi_pdf()
    {
        $user = $this->getTechUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate PDF as admin
        $admin = $this->getAdminUser();
        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        // Preview as tech
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/preview-pdf');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_customer_can_preview_cfdi_pdf()
    {
        $user = $this->getCustomerUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate PDF as admin
        $admin = $this->getAdminUser();
        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        // Preview as customer
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/preview-pdf');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_guest_cannot_preview_cfdi_pdf()
    {
        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $settings->id,
        ]);

        $response = $this->get('/api/v1/cfdi-invoices/' . $invoice->id . '/preview-pdf');

        $response->assertStatus(401);
    }

    /** @test XML Download Tests */

    public function test_admin_can_download_cfdi_xml()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate XML first
        $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        // Now download it
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xml', $response->headers->get('Content-Disposition'));
    }

    public function test_tech_can_download_cfdi_xml()
    {
        $user = $this->getTechUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate XML as admin
        $admin = $this->getAdminUser();
        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        // Download as tech
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/xml');
    }

    public function test_customer_can_download_cfdi_xml()
    {
        $user = $this->getCustomerUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        // Generate XML as admin
        $admin = $this->getAdminUser();
        $this->withHeader('Authorization', 'Bearer ' . $admin->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-xml');

        // Download as customer
        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/xml');
    }

    public function test_guest_cannot_download_cfdi_xml()
    {
        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $settings->id,
        ]);

        $response = $this->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertStatus(401);
    }

    public function test_xml_download_prefers_stamped_over_original()
    {
        $user = $this->getAdminUser();

        $originalXml = '<?xml version="1.0"?><cfdi:Comprobante>Original</cfdi:Comprobante>';
        $stampedXml = '<?xml version="1.0"?><cfdi:Comprobante>Stamped</cfdi:Comprobante>';

        $invoice = CFDIInvoice::factory()->create([
            'xml_original' => $originalXml,
            'xml_timbrado' => $stampedXml,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertSuccessful();
        $this->assertStringContainsString('Stamped', $response->getContent());
    }

    public function test_xml_download_falls_back_to_original_if_no_stamped()
    {
        $user = $this->getAdminUser();

        $originalXml = '<?xml version="1.0"?><cfdi:Comprobante>Original</cfdi:Comprobante>';

        $invoice = CFDIInvoice::factory()->create([
            'xml_original' => $originalXml,
            'xml_timbrado' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertSuccessful();
        $this->assertStringContainsString('Original', $response->getContent());
    }

    public function test_xml_download_returns_404_if_no_xml_available()
    {
        $user = $this->getAdminUser();

        $invoice = CFDIInvoice::factory()->create([
            'xml_original' => null,
            'xml_timbrado' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertStatus(404);
    }

    public function test_xml_download_has_correct_filename_for_draft()
    {
        $user = $this->getAdminUser();

        $invoice = CFDIInvoice::factory()->create([
            'series' => 'B',
            'folio' => 456,
            'uuid' => null,
            'xml_original' => '<?xml version="1.0"?><cfdi:Comprobante></cfdi:Comprobante>',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertSuccessful();

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('CFDI_B_000456_DRAFT.xml', $contentDisposition);
    }

    public function test_xml_download_has_correct_filename_for_stamped()
    {
        $user = $this->getAdminUser();

        $uuid = '12345678-1234-1234-1234-123456789012';
        $invoice = CFDIInvoice::factory()->create([
            'series' => 'C',
            'folio' => 789,
            'uuid' => $uuid,
            'xml_timbrado' => '<?xml version="1.0"?><cfdi:Comprobante></cfdi:Comprobante>',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id . '/download-xml');

        $response->assertSuccessful();

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('CFDI_C_000789_' . $uuid . '.xml', $contentDisposition);
    }
}

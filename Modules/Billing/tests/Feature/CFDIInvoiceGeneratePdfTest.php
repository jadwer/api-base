<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;

class CFDIInvoiceGeneratePdfTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_generate_cfdi_pdf()
    {
        $user = $this->getAdminUser();

        // Create company settings
        $settings = CompanySetting::factory()->create(['is_active' => true]);

        // Create invoice with items
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(2), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertSuccessful()
            ->assertJson([
                'message' => 'PDF CFDI generado correctamente',
                'invoiceId' => $invoice->id,
            ])
            ->assertJsonStructure([
                'message',
                'pdf_path',
                'pdf_url',
                'invoiceId',
            ]);

        // Verify PDF path was stored in database
        $invoice->refresh();
        $this->assertNotNull($invoice->pdf_path);

        // Verify PDF file exists in storage
        $this->assertTrue(Storage::disk('public')->exists($invoice->pdf_path));
    }

    public function test_generated_pdf_file_is_created_in_correct_location()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertSuccessful();

        $invoice->refresh();

        // Verify path follows pattern: cfdi/invoices/{id}/CFDI_{serie}_{folio}...pdf
        $this->assertStringContainsString("cfdi/invoices/{$invoice->id}/", $invoice->pdf_path);
        $this->assertStringContainsString('.pdf', $invoice->pdf_path);

        // Verify file exists
        $this->assertTrue(Storage::disk('public')->exists($invoice->pdf_path));

        // Verify file is not empty
        $this->assertGreaterThan(0, Storage::disk('public')->size($invoice->pdf_path));
    }

    public function test_pdf_generation_includes_qr_code_for_valid_invoice()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->valid()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertSuccessful();

        // Verify QR code was stored in database
        $invoice->refresh();
        $this->assertNotNull($invoice->qr_code);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $invoice->qr_code);
    }

    public function test_pdf_generation_creates_draft_pdf_for_unstamped_invoice()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->draft()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertSuccessful();

        $invoice->refresh();
        $this->assertStringContainsString('DRAFT', $invoice->pdf_path);
    }

    public function test_tech_cannot_generate_pdf()
    {
        $user = $this->getTechUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $settings->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para generar PDF CFDI',
            ]);
    }

    public function test_customer_cannot_generate_pdf()
    {
        $user = $this->getCustomerUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $settings->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para generar PDF CFDI',
            ]);
    }

    public function test_guest_cannot_generate_pdf()
    {
        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $settings->id,
        ]);

        $response = $this->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertStatus(401);
    }

    public function test_pdf_generation_fails_without_active_company_settings()
    {
        $user = $this->getAdminUser();

        // Deactivate all company settings
        CompanySetting::query()->update(['is_active' => false]);

        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Error al generar PDF CFDI',
            ]);
    }

    public function test_pdf_can_be_regenerated()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
                'pdf_path' => 'cfdi/invoices/old/old-file.pdf',
            ]);

        $oldPath = $invoice->pdf_path;

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertSuccessful();

        $invoice->refresh();
        $this->assertNotEquals($oldPath, $invoice->pdf_path);
        $this->assertTrue(Storage::disk('public')->exists($invoice->pdf_path));
    }

    public function test_pdf_generation_handles_invoice_with_discount()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->withDiscount()
            ->has(CFDIItem::factory()->count(1), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertSuccessful();

        $invoice->refresh();
        $this->assertTrue(Storage::disk('public')->exists($invoice->pdf_path));
    }

    public function test_pdf_generation_handles_invoice_with_taxes()
    {
        $user = $this->getAdminUser();

        $settings = CompanySetting::factory()->create(['is_active' => true]);
        $invoice = CFDIInvoice::factory()
            ->has(CFDIItem::factory()->withTaxes()->count(2), 'items')
            ->create([
                'companySettingId' => $settings->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/' . $invoice->id . '/generate-pdf');

        $response->assertSuccessful();

        $invoice->refresh();
        $this->assertTrue(Storage::disk('public')->exists($invoice->pdf_path));
    }

    public function test_pdf_generation_returns_404_for_nonexistent_invoice()
    {
        $user = $this->getAdminUser();

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/cfdi-invoices/99999/generate-pdf');

        $response->assertStatus(404);
    }
}

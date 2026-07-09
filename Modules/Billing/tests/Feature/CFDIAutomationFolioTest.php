<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\CFDIAutomationService;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;

class CFDIAutomationFolioTest extends TestCase
{
    protected CompanySetting $companySetting;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        // El servicio usa la primera CompanySetting activa; desactivar las seeded
        CompanySetting::query()->update(['is_active' => false]);

        $this->companySetting = CompanySetting::factory()->create([
            'is_active' => true,
            'invoice_series' => 'F',
            'next_invoice_folio' => 5,
        ]);

        $this->contact = Contact::factory()->create([
            'is_customer' => true,
            'tax_id' => 'XEXX010101000',
        ]);
    }

    public function test_generate_from_ar_invoice_uses_next_invoice_folio_from_company_setting(): void
    {
        $arInvoice = ARInvoice::factory()->create([
            'contact_id' => $this->contact->id,
            'subtotal' => 100.00,
            'tax_amount' => 16.00,
            'total_amount' => 116.00,
            'status' => 'posted',
        ]);

        $service = app(CFDIAutomationService::class);
        $cfdi = $service->generateFromARInvoice($arInvoice);

        $this->assertEquals('F', $cfdi->series);
        $this->assertEquals(5, (int) $cfdi->folio);
    }

    public function test_generate_from_ar_invoice_increments_next_invoice_folio(): void
    {
        $arInvoice = ARInvoice::factory()->create([
            'contact_id' => $this->contact->id,
            'subtotal' => 100.00,
            'tax_amount' => 16.00,
            'total_amount' => 116.00,
            'status' => 'posted',
        ]);

        $service = app(CFDIAutomationService::class);
        $service->generateFromARInvoice($arInvoice);

        $this->assertEquals(6, $this->companySetting->fresh()->next_invoice_folio);

        $this->assertDatabaseHas('company_settings', [
            'id' => $this->companySetting->id,
            'next_invoice_folio' => 6,
        ]);
    }

    public function test_consecutive_cfdis_get_consecutive_folios(): void
    {
        $service = app(CFDIAutomationService::class);

        $first = $service->generateFromARInvoice(ARInvoice::factory()->create([
            'contact_id' => $this->contact->id,
            'subtotal' => 100.00,
            'tax_amount' => 16.00,
            'total_amount' => 116.00,
            'status' => 'posted',
        ]));

        $second = $service->generateFromARInvoice(ARInvoice::factory()->create([
            'contact_id' => $this->contact->id,
            'subtotal' => 200.00,
            'tax_amount' => 32.00,
            'total_amount' => 232.00,
            'status' => 'posted',
        ]));

        $this->assertEquals(5, (int) $first->folio);
        $this->assertEquals(6, (int) $second->folio);
        $this->assertEquals(7, $this->companySetting->fresh()->next_invoice_folio);
    }
}

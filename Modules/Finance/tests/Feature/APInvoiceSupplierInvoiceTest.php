<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Support\GlAccounts;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Services\APInvoiceService;
use Modules\Purchase\Models\PurchaseOrder;
use Tests\TestCase;

/**
 * IVA de compras (decision de la contadora): la orden de compra no lleva IVA;
 * la CxP nace por el subtotal recibido y el IVA entra al capturar la factura
 * del proveedor: Dr 1110 IVA acreditable pendiente / Cr 2101 Proveedores.
 */
class APInvoiceSupplierInvoiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            [
                'name' => now()->format('Y-m'),
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
            ]
        );
    }

    private function receivedApInvoice(float $subtotal = 1000.00): APInvoice
    {
        $supplier = Contact::factory()->create();

        return app(APInvoiceService::class)->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $supplier->id,
            'purchaseOrderId' => PurchaseOrder::factory()->create(['contact_id' => $supplier->id])->id,
            'currency' => 'MXN',
            'subtotal' => $subtotal,
            'taxAmount' => 0,
            'totalAmount' => $subtotal,
        ]);
    }

    private function balance(string $code): float
    {
        $id = Account::where('code', $code)->value('id');

        return $id ? round((float) JournalLine::where('account_id', $id)->sum('debit')
            - (float) JournalLine::where('account_id', $id)->sum('credit'), 2) : 0.0;
    }

    private function capture(APInvoice $invoice, array $data)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ap-invoices/{$invoice->id}/supplier-invoice", $data);
    }

    public function test_captura_el_iva_y_lo_contabiliza_como_acreditable_pendiente(): void
    {
        $invoice = $this->receivedApInvoice();
        $vatBefore = $this->balance(GlAccounts::VAT_INPUT_PENDING);
        $suppliersBefore = $this->balance(GlAccounts::SUPPLIERS);

        $this->capture($invoice, [
            'supplier_invoice_number' => 'A-1234',
            'supplier_uuid' => '5f0c5f2e-1c2b-4a3d-9e8f-0123456789ab',
            'subtotal' => 1000,
            'tax_amount' => 160,
        ])->assertOk()->assertJsonPath('invoice.total_amount', 1160);

        $invoice->refresh();
        $this->assertEquals(160.0, (float) $invoice->tax_amount);
        $this->assertEquals(1160.0, (float) $invoice->total_amount);
        $this->assertSame('A-1234', $invoice->metadata['supplier_invoice']['number']);
        $this->assertSame(160.0, round($this->balance(GlAccounts::VAT_INPUT_PENDING) - $vatBefore, 2));
        $this->assertSame(-160.0, round($this->balance(GlAccounts::SUPPLIERS) - $suppliersBefore, 2));
    }

    public function test_factura_sin_iva_se_captura_sin_poliza(): void
    {
        $invoice = $this->receivedApInvoice();
        $vatBefore = $this->balance(GlAccounts::VAT_INPUT_PENDING);

        $this->capture($invoice, ['supplier_invoice_number' => 'B-9', 'subtotal' => 1000, 'tax_amount' => 0])->assertOk();

        $this->assertSame('B-9', $invoice->fresh()->metadata['supplier_invoice']['number']);
        $this->assertSame($vatBefore, $this->balance(GlAccounts::VAT_INPUT_PENDING));
    }

    public function test_no_se_captura_dos_veces(): void
    {
        $invoice = $this->receivedApInvoice();
        $this->capture($invoice, ['supplier_invoice_number' => 'A-1', 'subtotal' => 1000, 'tax_amount' => 160])->assertOk();

        $second = $this->capture($invoice, ['supplier_invoice_number' => 'A-2', 'subtotal' => 1000, 'tax_amount' => 160]);
        $second->assertStatus(422);
        $this->assertStringContainsString('ya se capturó', $second->json('error'));
        $this->assertEquals(1160.0, (float) $invoice->fresh()->total_amount);
    }

    public function test_subtotal_distinto_de_lo_recibido_se_rechaza_con_detalle(): void
    {
        $invoice = $this->receivedApInvoice();

        $res = $this->capture($invoice, ['supplier_invoice_number' => 'A-1', 'subtotal' => 1010, 'tax_amount' => 161.60]);

        $res->assertStatus(422);
        $this->assertStringContainsString('no coincide con lo recibido ($1,000.00)', $res->json('error'));
        $this->assertEquals(0.0, (float) $invoice->fresh()->tax_amount);
    }

    public function test_iva_mayor_al_16_se_rechaza(): void
    {
        $res = $this->capture($this->receivedApInvoice(), ['supplier_invoice_number' => 'A-1', 'subtotal' => 1000, 'tax_amount' => 200]);

        $res->assertStatus(422);
        $this->assertStringContainsString('16 %', $res->json('error'));
    }

    public function test_cxp_con_pagos_no_se_toca(): void
    {
        $invoice = $this->receivedApInvoice();
        $invoice->forceFill(['paid_amount' => 100])->save();

        $res = $this->capture($invoice, ['supplier_invoice_number' => 'A-1', 'subtotal' => 1000, 'tax_amount' => 160]);

        $res->assertStatus(422);
        $this->assertStringContainsString('sin pagos', $res->json('error'));
    }

    public function test_validaciones_en_espanol(): void
    {
        $res = $this->capture($this->receivedApInvoice(), ['supplier_uuid' => 'no-es-uuid', 'subtotal' => 1000]);

        $res->assertStatus(422)->assertJsonValidationErrors(['supplier_invoice_number', 'supplier_uuid', 'tax_amount']);
        $this->assertSame('Indica el folio de la factura del proveedor.', $res->json('errors.supplier_invoice_number.0'));
    }

    public function test_sin_permiso_403(): void
    {
        $invoice = $this->receivedApInvoice();

        $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->postJson("/api/v1/ap-invoices/{$invoice->id}/supplier-invoice", ['supplier_invoice_number' => 'A-1', 'subtotal' => 1000, 'tax_amount' => 160])
            ->assertStatus(403);
    }
}

<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Tests\TestCase;

/**
 * Paquete B: los Resources manuales de dinero deben devolver TODO lo que el
 * Schema declara (patron Resource-pisa-Schema; asserta el RESPONSE, no la
 * base). Antes el descuento por pronto pago (FI-M002) y los campos del REP
 * se guardaban pero el API jamas los devolvia.
 */
class MoneyResourceFieldsTest extends TestCase
{
    public function test_ar_invoice_response_exposes_early_payment_discount_fields(): void
    {
        $contact = Contact::factory()->create(['is_customer' => true]);
        $invoice = ARInvoice::create([
            'invoice_number' => 'AR-DISC-' . uniqid(),
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'contact_id' => $contact->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'total_amount' => 116,
            'status' => 'posted',
            'discount_percent' => 2.5,
            'discount_days' => 10,
            'discount_date' => '2026-08-11',
            'discount_amount' => 2.9,
        ]);

        $attrs = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()->expects('ar-invoices')
            ->get("/api/v1/ar-invoices/{$invoice->id}")
            ->json('data.attributes');

        $this->assertEquals(2.5, $attrs['discountPercent']);
        $this->assertEquals(10, $attrs['discountDays']);
        $this->assertEquals(2.9, $attrs['discountAmount']);
        $this->assertArrayHasKey('discountApplied', $attrs);
        $this->assertArrayHasKey('paidDate', $attrs);
    }

    public function test_cfdi_invoice_response_exposes_rep_fields(): void
    {
        $contact = Contact::factory()->create(['is_customer' => true]);
        $settings = \Modules\Billing\Models\CompanySetting::factory()->create(['is_active' => true]);
        $ar = ARInvoice::create([
            'invoice_number' => 'AR-REP-' . uniqid(),
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'contact_id' => $contact->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'total_amount' => 116,
            'status' => 'posted',
        ]);
        $invoice = CFDIInvoice::create([
            'company_setting_id' => $settings->id,
            'contact_id' => $contact->id,
            'series' => 'P',
            'folio' => 77,
            'tipo_comprobante' => 'P',
            'receptor_rfc' => 'XAXX010101000',
            'receptor_nombre' => 'Cliente REP',
            'subtotal' => 0,
            'total' => 0,
            'status' => 'draft',
            'ar_invoice_id' => $ar->id,
            'fecha_emision' => '2026-08-15 10:00:00',
            'fecha_pago' => '2026-08-15 10:00:00',
            'monto_pago' => 11600,
            'forma_pago_p' => '03',
            'num_parcialidad' => 1,
            'imp_saldo_insoluto' => 0,
        ]);

        $attrs = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()->expects('cfdi-invoices')
            ->get("/api/v1/cfdi-invoices/{$invoice->id}")
            ->json('data.attributes');

        $this->assertEquals($ar->id, $attrs['arInvoiceId']);
        $this->assertEquals(11600, $attrs['montoPago']);
        $this->assertEquals('03', $attrs['formaPagoP']);
        $this->assertEquals(1, $attrs['numParcialidad']);
        $this->assertArrayHasKey('impSaldoInsoluto', $attrs);
        $this->assertArrayHasKey('fechaPago', $attrs);
    }

    public function test_payment_transaction_response_never_exposes_client_secret(): void
    {
        // clientSecret es hidden en el Schema y el Resource no debe
        // exponerlo jamas (el barrido lo excluyo a proposito).
        $resource = file_get_contents(base_path('Modules/Billing/app/JsonApi/V1/PaymentTransactions/PaymentTransactionResource.php'));
        $this->assertDoesNotMatchRegularExpression(
            "/['\"]clientSecret['\"]\s*=>/",
            $resource,
            'clientSecret expuesto en el Resource (es hidden, jamas debe salir)'
        );
    }
}

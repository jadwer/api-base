<?php

namespace Modules\Billing\Tests\Feature;

use Modules\Accounting\Models\FiscalPeriod;
use Modules\Billing\Support\CfdiReceptor;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\ContactAddress;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * Bug 2026-09-30: el CFDI automatico leia columnas inexistentes del contacto
 * y salia con CP 00000, regimen 616 y uso G03; la factura AR ignoraba los
 * dias de credito de la OV.
 */
class CfdiReceptorTest extends TestCase
{
    public function test_receptor_uses_the_contact_fiscal_data(): void
    {
        $contact = Contact::factory()->customer()->create([
            'name' => 'Lab Canales',
            'legal_name' => 'LABORATORIOS CANALES SA DE CV',
            'tax_id' => 'LCA010101AB1',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G01',
        ]);
        ContactAddress::factory()->create(['contact_id' => $contact->id, 'address_type' => 'shipping', 'postal_code' => '01000', 'is_default' => true]);
        ContactAddress::factory()->create(['contact_id' => $contact->id, 'address_type' => 'fiscal', 'postal_code' => '50000', 'is_default' => false]);

        $this->assertSame([
            'receptor_rfc' => 'LCA010101AB1',
            'receptor_nombre' => 'LABORATORIOS CANALES SA DE CV',
            'receptor_domicilio_fiscal' => '50000',
            'receptor_regimen_fiscal' => '601',
            'receptor_uso_cfdi' => 'G01',
        ], CfdiReceptor::fromContact($contact->fresh(), '52900'));
    }

    public function test_without_rfc_it_is_publico_en_general_with_emitter_zip(): void
    {
        $contact = Contact::factory()->customer()->create(['tax_id' => null]);

        $receptor = CfdiReceptor::fromContact($contact, '52900');

        $this->assertSame('XAXX010101000', $receptor['receptor_rfc']);
        $this->assertSame('PUBLICO EN GENERAL', $receptor['receptor_nombre']);
        $this->assertSame('52900', $receptor['receptor_domicilio_fiscal']);
        $this->assertSame('S01', $receptor['receptor_uso_cfdi']);
    }

    public function test_ar_invoice_due_date_follows_the_order_credit_days(): void
    {
        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            ['name' => now()->format('Y-m'), 'start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString(), 'status' => 'open']
        );
        $contact = Contact::factory()->customer()->create(['payment_terms' => 30, 'status' => 'active']);
        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'delivered',
            'credit_days' => 15,
            'total_amount' => 116,
            'subtotal' => 100,
            'tax_amount' => 16,
        ]);

        $invoice = app(ARInvoiceService::class)->createFromSalesOrder($order);

        $this->assertSame(now()->addDays(15)->toDateString(), $invoice->due_date->toDateString());
    }
}

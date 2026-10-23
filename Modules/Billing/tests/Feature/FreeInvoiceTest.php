<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\InvoiceSeries;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\ContactAddress;
use Modules\Finance\Models\ARInvoice;
use Tests\TestCase;

/**
 * Factura libre estilo Bind (junta 2026-10-08): borrador de CFDI con
 * conceptos capturados, ligado a su cuenta por cobrar y con folio de la
 * fuente unica de series.
 */
class FreeInvoiceTest extends TestCase
{
    private CompanySetting $settings;
    private Contact $customer;

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

        CompanySetting::query()->update(['is_active' => false]);
        $this->settings = CompanySetting::factory()->create(['is_active' => true, 'postal_code' => '64000', 'invoice_series' => 'FAC', 'next_invoice_folio' => 1]);
        InvoiceSeries::create([
            'company_setting_id' => $this->settings->id, 'code' => 'FAC', 'name' => 'Facturacion Normal',
            'cfdi_type' => 'I', 'current_folio' => 0, 'initial_folio' => 1, 'folio_padding' => 6,
            'separator' => '-', 'is_active' => true, 'is_default' => true,
        ]);
        InvoiceSeries::create([
            'company_setting_id' => $this->settings->id, 'code' => 'SRV', 'name' => 'Servicios',
            'cfdi_type' => 'I', 'current_folio' => 9, 'initial_folio' => 1, 'folio_padding' => 6,
            'separator' => '-', 'is_active' => true, 'is_default' => false,
        ]);

        DB::table('sat_forma_pago')->insertOrIgnore([['clave' => '03', 'descripcion' => 'Transferencia electrónica de fondos'], ['clave' => '99', 'descripcion' => 'Por definir']]);
        DB::table('sat_clave_prod_serv')->insertOrIgnore([['clave' => '81111500', 'descripcion' => 'Ingeniería de software o hardware']]);
        DB::table('sat_clave_unidad')->insertOrIgnore([['clave' => 'E48', 'nombre' => 'Unidad de servicio']]);

        $this->customer = Contact::factory()->customer()->create([
            'tax_id' => 'ABC010101AB1', 'legal_name' => 'Cliente Libre SA de CV',
            'regimen_fiscal' => '601', 'uso_cfdi' => 'G03', 'payment_terms' => 15,
        ]);
        ContactAddress::factory()->create(['contact_id' => $this->customer->id, 'address_type' => 'fiscal', 'postal_code' => '06600']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'contact_id' => $this->customer->id,
            'metodo_pago' => 'PPD',
            'forma_pago' => '99',
            'items' => [
                ['descripcion' => 'Calibración de equipo', 'cantidad' => 2, 'valor_unitario' => 1000, 'descuento' => 100,
                    'clave_prod_serv' => '81111500', 'clave_unidad' => 'E48', 'tasa_iva' => '0.160000'],
                ['descripcion' => 'Traslado', 'cantidad' => 1, 'valor_unitario' => 500,
                    'clave_prod_serv' => '81111500', 'clave_unidad' => 'E48', 'tasa_iva' => 'no_objeto'],
            ],
        ], $overrides);
    }

    public function test_creates_draft_cfdi_with_items_ar_invoice_and_folio(): void
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/cfdi-invoices/free', $this->payload());

        $response->assertCreated()->assertJsonPath('data.series', 'FAC')->assertJsonPath('data.folio', 1);

        $cfdi = CFDIInvoice::with('items')->findOrFail($response->json('data.id'));
        // 2 x 1000 - 100 = 1900 con IVA 16% (304) + 500 no objeto
        $this->assertSame(250000, $cfdi->subtotal);
        $this->assertSame(10000, $cfdi->descuento);
        $this->assertSame(30400, $cfdi->iva);
        $this->assertSame(270400, $cfdi->total);
        $this->assertSame('draft', $cfdi->status);
        $this->assertSame('ABC010101AB1', $cfdi->receptor_rfc);
        $this->assertSame('06600', $cfdi->receptor_domicilio_fiscal);
        $this->assertSame('99', $cfdi->forma_pago);
        $this->assertSame('Crédito a 15 días', $cfdi->condiciones_pago);

        $this->assertCount(2, $cfdi->items);
        $this->assertSame(190000, $cfdi->items[0]->impuestos['traslados'][0]['base']);
        $this->assertSame('01', $cfdi->items[1]->objeto_imp);

        $ar = ARInvoice::findOrFail($cfdi->ar_invoice_id);
        $this->assertEqualsWithDelta(2704.00, (float) $ar->total_amount, 0.001);
        $this->assertNotNull($ar->journal_entry_id);
        $this->assertSame(now()->addDays(15)->toDateString(), $ar->due_date->toDateString());
    }

    public function test_chosen_series_uses_its_own_counter(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/cfdi-invoices/free', $this->payload(['series' => 'SRV']))
            ->assertCreated()
            ->assertJsonPath('data.series', 'SRV')
            ->assertJsonPath('data.folio', 10);
    }

    public function test_folio_never_repeats_an_issued_one(): void
    {
        // Un CFDI ya emitido en FAC-1 con el contador historico (camino viejo)
        CFDIInvoice::factory()->create(['company_setting_id' => $this->settings->id, 'series' => 'FAC', 'folio' => 1]);

        // La vista previa anuncia el mismo folio que se asignara
        $this->actingAs($this->getAdminUser(), 'sanctum')->getJson('/api/v1/cfdi-invoices/free/options')
            ->assertJsonPath('data.series.0.next_folio', 'FAC-2');

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/cfdi-invoices/free', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.folio', 2);
    }

    public function test_sat_payment_rules_and_spanish_messages(): void
    {
        $admin = $this->getAdminUser();
        $before = CFDIInvoice::count();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/cfdi-invoices/free', $this->payload(['metodo_pago' => 'PUE', 'forma_pago' => '99']))
            ->assertStatus(422)
            ->assertJsonPath('errors.forma_pago.0', 'Con método PUE indica la forma de pago real; 99 (Por definir) solo aplica a PPD.');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/cfdi-invoices/free', $this->payload(['items' => [0 => ['clave_prod_serv' => '99999999']]]));
        $response->assertStatus(422);
        $this->assertStringContainsString('clave SAT de producto del concepto 1', $response->json('errors')['items.0.clave_prod_serv'][0]);

        $this->assertSame($before, CFDIInvoice::count());
    }

    public function test_customer_without_fiscal_postal_code_is_rejected_with_detail(): void
    {
        ContactAddress::where('contact_id', $this->customer->id)->delete();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/cfdi-invoices/free', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('message', 'El cliente no tiene código postal fiscal: captura su dirección fiscal antes de facturar.');

        $this->assertSame(0, ARInvoice::where('contact_id', $this->customer->id)->count());
    }

    public function test_options_and_receptor_endpoints(): void
    {
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/cfdi-invoices/free/options')
            ->assertOk()
            ->assertJsonPath('data.series.0.code', 'FAC')
            ->assertJsonPath('data.series.0.next_folio', 'FAC-1')
            ->assertJsonFragment(['code' => 'PPD'])
            ->assertJsonFragment(['code' => 'no_objeto']);

        $this->actingAs($admin, 'sanctum')->getJson("/api/v1/cfdi-invoices/free/receptor/{$this->customer->id}")
            ->assertOk()
            ->assertJsonPath('data.rfc', 'ABC010101AB1')
            ->assertJsonPath('data.codigo_postal', '06600')
            ->assertJsonPath('data.faltantes', []);

        // Sin regimen capturado no se inventa el 616
        $this->customer->update(['regimen_fiscal' => null]);
        $this->actingAs($admin, 'sanctum')->getJson("/api/v1/cfdi-invoices/free/receptor/{$this->customer->id}")
            ->assertJsonPath('data.regimen_fiscal', null)
            ->assertJsonPath('data.faltantes', ['Régimen fiscal']);
    }

    public function test_guest_cannot_create(): void
    {
        $this->postJson('/api/v1/cfdi-invoices/free', $this->payload())->assertUnauthorized();
    }
}

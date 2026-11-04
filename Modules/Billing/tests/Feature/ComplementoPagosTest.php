<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CfdiPaymentDoc;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\CFDI\REPService;
use Modules\Billing\Services\PAC\SWPacService;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentMethod;
use Modules\Finance\Services\PaymentApplicationService;
use Tests\TestCase;

/**
 * Complemento de Pagos 2.0 (REP) — cubre los 8 edge cases del diseño.
 *
 * El PAC SIEMPRE se mockea (patron CFDIStampingTest): NUNCA se llama al PAC real.
 */
class ComplementoPagosTest extends TestCase
{
    use RefreshDatabase;

    protected CompanySetting $companySetting;
    protected Contact $contact;

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

        $this->companySetting = CompanySetting::factory()->create([
            'rfc' => 'XAXX010101000',
            'company_name' => 'Test Company SA de CV',
            'is_active' => true,
        ]);

        $this->contact = Contact::factory()->customer()->create([
            'tax_id' => 'XEXX010101000',
        ]);
    }

    /**
     * Mocks SWPacService: enabled + stamp() devuelve un UUID falso.
     * NUNCA toca el PAC real.
     */
    private function mockPacEnabled(): void
    {
        $counter = 0;
        $mock = Mockery::mock(SWPacService::class);
        $mock->shouldReceive('isEnabled')->andReturn(true);
        $mock->shouldReceive('stamp')->andReturnUsing(function () use (&$counter) {
            $counter++;
            return [
                'uuid' => sprintf('REP0-UUID-0000-0000-%012d', $counter),
                'fecha_timbrado' => now(),
                'xml_timbrado' => '<cfdi:Comprobante/>',
                'qr_code' => null,
                'pac_response' => ['status' => 'success'],
            ];
        });
        $this->app->instance(SWPacService::class, $mock);
    }

    private function mockPacDisabled(): void
    {
        $mock = Mockery::mock(SWPacService::class);
        $mock->shouldReceive('isEnabled')->andReturn(false);
        $mock->shouldReceive('stamp')->never();
        $this->app->instance(SWPacService::class, $mock);
    }

    private function makePpdInvoiceWithCfdi(float $total, bool $timbrada = true, string $metodoPago = 'PPD'): array
    {
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $this->contact->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'currency' => 'MXN',
            'subtotal' => round($total / 1.16, 2),
            'tax_amount' => round($total - $total / 1.16, 2),
            'total_amount' => $total,
            'paid_amount' => 0,
            'status' => 'posted',
            'is_active' => true,
        ]);

        $cfdi = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->companySetting->id,
            'contact_id' => $this->contact->id,
            'ar_invoice_id' => $invoice->id,
            'tipo_comprobante' => 'I',
            'metodo_pago' => $metodoPago,
            'series' => 'F',
            'folio' => 500,
            'uuid' => $timbrada ? 'FAC0-UUID-0000-0000-000000000001' : null,
            'fecha_timbrado' => $timbrada ? now() : null,
            'status' => $timbrada ? 'valid' : 'draft',
            'total' => (int) round($total * 100),
        ]);

        return [$invoice, $cfdi];
    }

    /**
     * Registra un abono via PaymentApplicationService (dispara ARPaymentApplied).
     * Para tests que ejercen el REPService en aislamiento, se usa con el listener
     * de auto-generacion no interfiriendo porque el servicio es idempotente.
     */
    private function applyAbono(ARInvoice $invoice, float $amount, ?string $formaPago = '03', string $methodCode = '03'): Payment
    {
        $bank = BankAccount::factory()->create(['is_active' => true, 'status' => 'active']);
        $method = PaymentMethod::firstOrCreate(
            ['code' => $methodCode],
            ['name' => 'Metodo ' . $methodCode, 'type' => 'sat_forma_pago', 'requires_reference' => false, 'is_active' => true]
        );

        $payment = Payment::create([
            'payment_number' => 'PAY-' . fake()->unique()->numerify('######'),
            'payment_date' => now(),
            'contact_id' => $invoice->contact_id,
            'bank_account_id' => $bank->id,
            'payment_method_id' => $method->id,
            'amount' => $amount,
            'currency' => 'MXN',
            'applied_amount' => 0,
            'unapplied_amount' => $amount,
            'status' => 'unapplied',
            'metadata' => $formaPago ? ['forma_pago' => $formaPago] : [],
            'is_active' => true,
        ]);

        app(PaymentApplicationService::class)->applyPayment($payment, $invoice->fresh(), $amount);

        return $payment->fresh();
    }

    // ── Edge case 1: Abono a factura PUE: NO genera REP ──────────────────────

    public function test_abono_a_factura_pue_no_genera_rep(): void
    {
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00, timbrada: true, metodoPago: 'PUE');

        $payment = $this->applyAbono($invoice, 1000.00);

        $rep = app(REPService::class)->generateFromPayment($payment);

        $this->assertNull($rep);
        $this->assertDatabaseMissing('cfdi_invoices', [
            'ar_payment_id' => $payment->id,
            'tipo_comprobante' => 'P',
        ]);
    }

    // ── Edge case 2: Factura PPD sin timbrar (sin uuid): NO genera ───────────

    public function test_abono_a_factura_ppd_sin_timbrar_no_genera_rep(): void
    {
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00, timbrada: false);

        $payment = $this->applyAbono($invoice, 400.00);

        $rep = app(REPService::class)->generateFromPayment($payment);

        $this->assertNull($rep);
    }

    // ── Edge case: abono normal a PPD timbrada genera REP tipo P ─────────────

    public function test_abono_a_factura_ppd_timbrada_genera_rep(): void
    {
        $this->mockPacEnabled();
        [$invoice, $cfdi] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment = $this->applyAbono($invoice, 400.00);

        $rep = app(REPService::class)->generateFromPayment($payment);

        $this->assertNotNull($rep);
        $this->assertEquals('P', $rep->tipo_comprobante);
        $this->assertEquals($payment->id, $rep->ar_payment_id);
        $this->assertEquals(40000, $rep->monto_pago); // 400.00 en centavos
        $this->assertEquals('valid', $rep->status); // timbrado (PAC mock enabled)
        $this->assertNotNull($rep->uuid);
        $this->assertStringStartsWith('REP0-UUID', $rep->uuid);

        $doc = CfdiPaymentDoc::where('payment_cfdi_id', $rep->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals(1, $doc->num_parcialidad);
        $this->assertEquals(100000, $doc->imp_saldo_ant);        // 1000.00
        $this->assertEquals(40000, $doc->imp_pagado);            // 400.00
        $this->assertEquals(60000, $doc->imp_saldo_insoluto);    // 600.00
        $this->assertEquals($cfdi->uuid, $doc->related_uuid);
    }

    // ── Edge case 3: Segundo abono: NumParcialidad=2, saldos encadenados ─────

    public function test_segundo_abono_incrementa_parcialidad_y_encadena_saldos(): void
    {
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment1 = $this->applyAbono($invoice, 400.00);
        app(REPService::class)->generateFromPayment($payment1);

        $payment2 = $this->applyAbono($invoice->fresh(), 300.00);
        $rep2 = app(REPService::class)->generateFromPayment($payment2);

        $doc2 = CfdiPaymentDoc::where('payment_cfdi_id', $rep2->id)->first();
        $this->assertEquals(2, $doc2->num_parcialidad);
        $this->assertEquals(60000, $doc2->imp_saldo_ant);        // 600.00 (tras primer abono)
        $this->assertEquals(30000, $doc2->imp_pagado);           // 300.00
        $this->assertEquals(30000, $doc2->imp_saldo_insoluto);   // 300.00
    }

    // ── Edge case 4: Abono que liquida: ImpSaldoInsoluto=0 ───────────────────

    public function test_abono_que_liquida_deja_saldo_insoluto_en_cero(): void
    {
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment = $this->applyAbono($invoice, 1000.00);
        $rep = app(REPService::class)->generateFromPayment($payment);

        $doc = CfdiPaymentDoc::where('payment_cfdi_id', $rep->id)->first();
        $this->assertEquals(1, $doc->num_parcialidad);
        $this->assertEquals(100000, $doc->imp_saldo_ant);
        $this->assertEquals(100000, $doc->imp_pagado);
        $this->assertEquals(0, $doc->imp_saldo_insoluto);
    }

    // ── Edge case 5: Reintento del mismo abono: idempotente ──────────────────

    public function test_reintento_del_mismo_abono_es_idempotente(): void
    {
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment = $this->applyAbono($invoice, 400.00);

        $rep1 = app(REPService::class)->generateFromPayment($payment);
        $rep2 = app(REPService::class)->generateFromPayment($payment);

        $this->assertEquals($rep1->id, $rep2->id);
        $this->assertEquals(
            1,
            CFDIInvoice::where('ar_payment_id', $payment->id)->where('tipo_comprobante', 'P')->count()
        );
    }

    // ── Edge case 6: SW off: CFDI P en draft, sin timbrar, sin romper ────────

    public function test_pac_deshabilitado_deja_rep_en_draft_sin_timbrar(): void
    {
        $this->mockPacDisabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment = $this->applyAbono($invoice, 400.00);
        $rep = app(REPService::class)->generateFromPayment($payment);

        $this->assertNotNull($rep);
        $this->assertEquals('draft', $rep->status);
        $this->assertNull($rep->uuid);
        // El XML original sí se genera aunque no se timbre.
        $this->assertNotEmpty($rep->xml_original);
        $this->assertStringContainsString('pago20:Pagos', $rep->xml_original);
    }

    // ── XML: contiene el namespace Pagos20 y los nodos requeridos ────────────

    public function test_xml_del_rep_incluye_namespace_pagos20_y_nodos(): void
    {
        $this->mockPacDisabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment = $this->applyAbono($invoice, 400.00);
        $rep = app(REPService::class)->generateFromPayment($payment);

        $xml = $rep->xml_original;
        $this->assertStringContainsString('http://www.sat.gob.mx/Pagos20', $xml);
        $this->assertStringContainsString('TipoDeComprobante="P"', $xml);
        $this->assertStringContainsString('pago20:Totales', $xml);
        $this->assertStringContainsString('pago20:DoctoRelacionado', $xml);
        $this->assertStringContainsString('NumParcialidad="1"', $xml);
        // Emisor/Receptor reusados del trait comun.
        $this->assertStringContainsString('cfdi:Emisor', $xml);
        $this->assertStringContainsString('cfdi:Receptor', $xml);
    }

    // ── Auto-disparo via listener sobre ARPaymentApplied (QUEUE=sync inline) ──

    public function test_listener_auto_genera_rep_al_aplicar_abono(): void
    {
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        // applyAbono dispara ARPaymentApplied; el listener corre inline (sync).
        $payment = $this->applyAbono($invoice, 400.00);

        $this->assertDatabaseHas('cfdi_invoices', [
            'ar_payment_id' => $payment->id,
            'tipo_comprobante' => 'P',
            'status' => 'valid',
        ]);
    }

    // ── Endpoint manual: emite REP para el ultimo abono ──────────────────────

    public function test_endpoint_manual_emite_rep(): void
    {
        // Con QUEUE=sync el listener ya genera el REP al aplicar el abono; el
        // endpoint manual devuelve el existente (idempotente, 200) o crea (201).
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment = $this->applyAbono($invoice, 400.00);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/payment-complement", [
                'payment_id' => $payment->id,
            ]);

        $this->assertContains($response->status(), [200, 201]);
        $response->assertJsonPath('data.tipo_comprobante', 'P')
            ->assertJsonPath('data.monto_pago', 40000);
    }

    public function test_endpoint_manual_422_si_factura_pue(): void
    {
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00, timbrada: true, metodoPago: 'PUE');

        $payment = $this->applyAbono($invoice, 1000.00);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/payment-complement", [
                'payment_id' => $payment->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_endpoint_manual_403_sin_permiso(): void
    {
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $response = $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/payment-complement", []);

        $response->assertStatus(403);
    }

    public function test_filtro_tipo_comprobante_p_lista_solo_reps(): void
    {
        $this->mockPacEnabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);
        $payment = $this->applyAbono($invoice, 400.00);
        app(REPService::class)->generateFromPayment($payment);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('cfdi-invoices')
            ->filter(['tipoComprobante' => 'P'])
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('attributes.tipoComprobante')->unique();
        $this->assertEquals(['P'], $ids->values()->all());
    }

    // ── Pagos 2.0: FormaDePagoP real (nunca 99) ──────────────────────────────

    public function test_forma_de_pago_sale_del_metodo_de_pago_si_no_hay_metadata(): void
    {
        $this->mockPacDisabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment = $this->applyAbono($invoice, 400.00, null, '01');
        $rep = app(REPService::class)->generateFromPayment($payment);

        $this->assertSame('01', $rep->forma_pago_p);
        $this->assertStringContainsString('FormaDePagoP="01"', $rep->xml_original);
    }

    public function test_sin_forma_de_pago_sat_no_emite_rep_con_99(): void
    {
        $this->mockPacDisabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        // El listener (sync) ya intenta generarlo y solo registra el error.
        $payment = $this->applyAbono($invoice, 400.00, null, '99');
        $this->assertDatabaseMissing('cfdi_invoices', ['ar_payment_id' => $payment->id, 'tipo_comprobante' => 'P']);

        try {
            app(REPService::class)->generateFromPayment($payment);
            $this->fail('Debio rechazar el REP sin forma de pago del SAT');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('forma de pago del SAT', $e->getMessage());
        }
        $this->assertDatabaseMissing('cfdi_invoices', ['ar_payment_id' => $payment->id, 'tipo_comprobante' => 'P']);
    }

    public function test_endpoint_manual_422_en_espanol_sin_forma_de_pago(): void
    {
        $this->mockPacDisabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);
        $this->applyAbono($invoice, 400.00, null, '99');

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/payment-complement");

        $response->assertStatus(422);
        $this->assertStringContainsString('forma de pago del SAT', $response->json('error'));
    }

    // ── Pagos 2.0: ImpuestosDR / ImpuestosP / Totales con IVA ─────────────────

    private function addIvaItems(CFDIInvoice $cfdi, array $lines): void
    {
        foreach ($lines as $i => [$baseCents, $tasa]) {
            $cfdi->items()->create([
                'numero_linea' => $i + 1,
                'clave_prod_serv' => '01010101',
                'clave_unidad' => 'H87',
                'unidad' => 'Pieza',
                'cantidad' => 1,
                'descripcion' => 'Producto ' . ($i + 1),
                'valor_unitario' => $baseCents,
                'importe' => $baseCents,
                'descuento' => 0,
                'objeto_imp' => '02',
                'impuestos' => ['traslados' => [[
                    'base' => $baseCents, 'impuesto' => '002', 'tipo_factor' => 'Tasa',
                    'tasa_o_cuota' => $tasa, 'importe' => (int) round($baseCents * (float) $tasa),
                ]], 'retenciones' => []],
            ]);
        }
    }

    public function test_abono_a_factura_con_iva_16_declara_impuestos_proporcionales(): void
    {
        $this->mockPacDisabled();
        [$invoice, $cfdi] = $this->makePpdInvoiceWithCfdi(1160.00);
        $this->addIvaItems($cfdi, [[100000, '0.160000']]);

        $payment = $this->applyAbono($invoice, 580.00);
        $rep = app(REPService::class)->generateFromPayment($payment);
        $doc = $rep->paymentDocs()->first();
        $xml = $rep->xml_original;

        $this->assertSame('02', $doc->objeto_imp_dr);
        $this->assertSame('500.000000', $doc->impuestos_dr['traslados'][0]['base']);
        $this->assertSame('80.000000', $doc->impuestos_dr['traslados'][0]['importe']);
        $this->assertStringContainsString('ObjetoImpDR="02"', $xml);
        $this->assertStringContainsString('<pago20:TrasladoDR BaseDR="500.000000" ImpuestoDR="002" TipoFactorDR="Tasa" TasaOCuotaDR="0.160000" ImporteDR="80.000000"', $xml);
        $this->assertStringContainsString('<pago20:TrasladoP BaseP="500.000000" ImpuestoP="002" TipoFactorP="Tasa" TasaOCuotaP="0.160000" ImporteP="80.000000"', $xml);
        $this->assertStringContainsString('TotalTrasladosBaseIVA16="500.00"', $xml);
        $this->assertStringContainsString('TotalTrasladosImpuestoIVA16="80.00"', $xml);

        // Orden del XSD: ImpuestosP va despues de los DoctoRelacionado.
        $this->assertLessThan(strpos($xml, 'pago20:ImpuestosP'), strpos($xml, 'pago20:DoctoRelacionado'));
    }

    public function test_abono_irregular_cae_dentro_de_los_limites_del_sat(): void
    {
        $this->mockPacDisabled();
        [$invoice, $cfdi] = $this->makePpdInvoiceWithCfdi(1160.00);
        $this->addIvaItems($cfdi, [[100000, '0.160000']]);

        $payment = $this->applyAbono($invoice, 333.33);
        $rep = app(REPService::class)->generateFromPayment($payment);
        $t = $rep->paymentDocs()->first()->impuestos_dr['traslados'][0];

        $base = (float) $t['base'];
        $importe = (float) $t['importe'];
        // Limites de ImporteDR con 6 decimales (guia Pagos 2.0): inferior
        // truncado y superior redondeado hacia arriba a 6 decimales.
        $inferior = floor(($base - 0.0000005) * 0.16 * 1e6) / 1e6;
        $superior = ceil(($base + 0.0000005) * 0.16 * 1e6) / 1e6;
        $this->assertGreaterThanOrEqual($inferior, $importe);
        $this->assertLessThanOrEqual($superior, $importe);
        // Base + IVA reconstruyen el abono a centavos.
        $this->assertEqualsWithDelta(333.33, $base + $importe, 0.01);
    }

    public function test_factura_con_tasas_16_y_0_agrupa_por_tasa(): void
    {
        $this->mockPacDisabled();
        // 1000 al 16% (160) + 500 al 0% = 1660
        [$invoice, $cfdi] = $this->makePpdInvoiceWithCfdi(1660.00);
        $this->addIvaItems($cfdi, [[100000, '0.160000'], [50000, '0.000000']]);

        $payment = $this->applyAbono($invoice, 830.00); // mitad
        $rep = app(REPService::class)->generateFromPayment($payment);
        $xml = $rep->xml_original;

        $this->assertStringContainsString('TotalTrasladosBaseIVA16="500.00"', $xml);
        $this->assertStringContainsString('TotalTrasladosImpuestoIVA16="80.00"', $xml);
        $this->assertStringContainsString('TotalTrasladosBaseIVA0="250.00"', $xml);
        $this->assertStringContainsString('TotalTrasladosImpuestoIVA0="0.00"', $xml);
        $this->assertSame(2, substr_count($xml, '<pago20:TrasladoDR '));
    }

    public function test_factura_con_retenciones_se_rechaza_con_mensaje_claro(): void
    {
        $this->mockPacDisabled();
        [$invoice, $cfdi] = $this->makePpdInvoiceWithCfdi(1000.00);
        $cfdi->items()->create([
            'numero_linea' => 1, 'clave_prod_serv' => '80111600', 'clave_unidad' => 'E48', 'unidad' => 'Servicio',
            'cantidad' => 1, 'descripcion' => 'Servicio', 'valor_unitario' => 100000, 'importe' => 100000, 'descuento' => 0,
            'objeto_imp' => '02',
            'impuestos' => ['traslados' => [], 'retenciones' => [['base' => 100000, 'impuesto' => '001', 'tipo_factor' => 'Tasa', 'tasa_o_cuota' => '0.100000', 'importe' => 10000]]],
        ]);

        $payment = $this->applyAbono($invoice, 400.00);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('retenciones');
        app(REPService::class)->generateFromPayment($payment);
    }

    public function test_factura_sin_impuestos_sigue_con_objeto_imp_01(): void
    {
        $this->mockPacDisabled();
        [$invoice] = $this->makePpdInvoiceWithCfdi(1000.00);

        $payment = $this->applyAbono($invoice, 400.00);
        $rep = app(REPService::class)->generateFromPayment($payment);

        $this->assertSame('01', $rep->paymentDocs()->first()->objeto_imp_dr);
        $this->assertStringNotContainsString('pago20:ImpuestosP', $rep->xml_original);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

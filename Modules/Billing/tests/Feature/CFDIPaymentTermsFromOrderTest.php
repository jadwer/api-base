<?php

namespace Modules\Billing\Tests\Feature;

use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\CFDIAutomationService;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Models\PaymentMethod;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Tests\TestCase;

/**
 * MetodoPago y FormaPago del CFDI de ingreso salen del pedido (decision de
 * Gabino 31-10: credito = PPD + REP, contado = PUE). Antes el CFDI decidia PUE
 * solo si la factura ya estaba pagada: un cliente de contado facturado antes de
 * cobrar salia PPD con 99.
 */
class CFDIPaymentTermsFromOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CompanySetting::query()->update(['is_active' => false]);
        CompanySetting::factory()->create([
            'company_name' => 'Test Company S.A. de C.V.',
            'rfc' => 'TCO010101ABC',
            'tax_regime' => '601',
            'postal_code' => '06600',
            'is_active' => true,
        ]);
    }

    /** @return array{0: SalesOrder, 1: ARInvoice} */
    private function deliveredOrder(?string $paymentMethod, ?int $creditDays, string $arStatus = 'posted'): array
    {
        $contact = Contact::factory()->customer()->create(['tax_id' => 'XAXX010101000', 'payment_terms' => 0]);
        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'delivered',
            'payment_method' => $paymentMethod,
            'credit_days' => $creditDays,
            'subtotal' => 100,
            'tax_amount' => 16,
            'total_amount' => 116,
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'total' => 100,
        ]);
        $ar = ARInvoice::factory()->create([
            'sales_order_id' => $order->id,
            'contact_id' => $contact->id,
            'status' => $arStatus,
            'subtotal' => 100,
            'tax_amount' => 16,
            'total_amount' => 116,
        ]);

        return [$order, $ar];
    }

    public function test_pedido_a_credito_sale_ppd_con_99(): void
    {
        [, $ar] = $this->deliveredOrder('PPD', 30);

        $cfdi = app(CFDIAutomationService::class)->generateFromARInvoice($ar);

        $this->assertSame('PPD', $cfdi->metodo_pago);
        $this->assertSame('99', $cfdi->forma_pago);
        $this->assertStringContainsString('30 días', (string) $cfdi->condiciones_pago);
    }

    public function test_contado_sin_cobrar_exige_forma_de_pago_y_no_gasta_folio(): void
    {
        [, $ar] = $this->deliveredOrder('PUE', 0);
        $settings = CompanySetting::where('is_active', true)->first();

        try {
            app(CFDIAutomationService::class)->generateFromARInvoice($ar);
            $this->fail('Debio pedir la forma de pago');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('venta de contado (PUE)', $e->getMessage());
        }

        $this->assertDatabaseMissing('cfdi_invoices', ['ar_invoice_id' => $ar->id]);
        $this->assertSame($settings->next_invoice_folio, $settings->fresh()->next_invoice_folio);
    }

    public function test_contado_con_forma_indicada_sale_pue(): void
    {
        [, $ar] = $this->deliveredOrder('PUE', 0);

        $cfdi = app(CFDIAutomationService::class)->generateFromARInvoice($ar, '03');

        $this->assertSame('PUE', $cfdi->metodo_pago);
        $this->assertSame('03', $cfdi->forma_pago);
        $this->assertNull($cfdi->condiciones_pago);
    }

    public function test_pedido_sin_metodo_usa_dias_de_credito(): void
    {
        [, $ar] = $this->deliveredOrder(null, 15);

        $this->assertSame('PPD', app(CFDIAutomationService::class)->generateFromARInvoice($ar)->metodo_pago);
    }

    public function test_factura_ya_pagada_sale_pue_con_la_forma_del_cobro(): void
    {
        [, $ar] = $this->deliveredOrder('PPD', 30, 'paid');
        $method = PaymentMethod::firstOrCreate(
            ['code' => '28'],
            ['name' => 'Tarjeta de débito', 'type' => 'sat_forma_pago', 'requires_reference' => false, 'is_active' => true]
        );
        $payment = Payment::factory()->create([
            'contact_id' => $ar->contact_id,
            'bank_account_id' => BankAccount::factory()->create()->id,
            'payment_method_id' => $method->id,
            'amount' => 116,
            'metadata' => [],
        ]);
        PaymentApplication::create([
            'payment_id' => $payment->id,
            'ar_invoice_id' => $ar->id,
            'amount' => 116,
            'application_date' => now(),
            'is_active' => true,
        ]);

        $cfdi = app(CFDIAutomationService::class)->generateFromARInvoice($ar);

        $this->assertSame('PUE', $cfdi->metodo_pago);
        $this->assertSame('28', $cfdi->forma_pago);
    }

    public function test_endpoint_facturar_responde_422_en_espanol_y_luego_acepta_la_forma(): void
    {
        [$order] = $this->deliveredOrder('PUE', 0);
        $admin = $this->getAdminUser();

        $first = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/sales-orders/{$order->id}/facturar");
        $first->assertStatus(422);
        $this->assertSame('forma_pago', $first->json('requires'));
        $this->assertStringContainsString('forma de pago real', $first->json('error'));

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/facturar", ['forma_pago' => '99'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['forma_pago']);

        $ok = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/facturar", ['forma_pago' => '01']);
        $ok->assertStatus(201);
        $this->assertDatabaseHas('cfdi_invoices', ['id' => $ok->json('data.id'), 'metodo_pago' => 'PUE', 'forma_pago' => '01']);
    }
}

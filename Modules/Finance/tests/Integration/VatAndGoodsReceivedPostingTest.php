<?php

namespace Modules\Finance\Tests\Integration;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Support\GlAccounts;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentMethod;
use Modules\Finance\Services\APInvoiceService;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Finance\Services\PaymentApplicationService;
use Tests\TestCase;

/**
 * Reglas de poliza de la auditoria de finanzas 2026-10 (puntos 1 y 2):
 * el IVA no es ingreso ni costo, se reclasifica al cobrar, y la factura de
 * proveedor de una orden salda la cuenta puente en vez de abonar 2101 dos veces.
 */
class VatAndGoodsReceivedPostingTest extends TestCase
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

    /** Saldo deudor menos acreedor de una cuenta en todas las polizas. */
    private function balance(string $code): float
    {
        $id = Account::where('code', $code)->value('id');
        if (!$id) {
            return 0.0;
        }

        return round((float) JournalLine::where('account_id', $id)->sum('debit')
            - (float) JournalLine::where('account_id', $id)->sum('credit'), 2);
    }

    private function arInvoice(float $subtotal, float $tax): ARInvoice
    {
        return app(ARInvoiceService::class)->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => Contact::factory()->customer()->create()->id,
            'currency' => 'MXN',
            'subtotal' => $subtotal,
            'taxAmount' => $tax,
            'totalAmount' => $subtotal + $tax,
        ]);
    }

    private function payment(ARInvoice $invoice, float $amount): Payment
    {
        return Payment::factory()->create([
            'payment_date' => now(),
            'contact_id' => $invoice->contact_id,
            'bank_account_id' => BankAccount::factory()->create()->id,
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'amount' => $amount,
            'applied_amount' => 0,
            'unapplied_amount' => $amount,
            'status' => 'unapplied',
            'is_active' => true,
        ]);
    }

    public function test_venta_manda_el_iva_a_no_cobrado_y_no_a_ingresos(): void
    {
        $this->arInvoice(1000.00, 160.00);

        $this->assertSame(1160.00, $this->balance(GlAccounts::CUSTOMERS));
        $this->assertSame(-1000.00, $this->balance(GlAccounts::REVENUE));
        $this->assertSame(-160.00, $this->balance(GlAccounts::VAT_OUTPUT_PENDING));
        $this->assertSame(0.0, $this->balance(GlAccounts::VAT_OUTPUT));
    }

    public function test_cobros_parciales_reclasifican_el_iva_exacto_al_liquidar(): void
    {
        $invoice = $this->arInvoice(1000.00, 160.00);
        $service = app(PaymentApplicationService::class);

        // Tres abonos con centavos que no dividen parejo.
        foreach ([333.33, 333.33, 493.34] as $amount) {
            $service->applyPayment($this->payment($invoice, $amount), $invoice->fresh(), $amount);
        }

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0.0, $this->balance(GlAccounts::VAT_OUTPUT_PENDING));
        $this->assertSame(-160.00, $this->balance(GlAccounts::VAT_OUTPUT));
        $this->assertSame(0.0, $this->balance(GlAccounts::CUSTOMERS));
    }

    public function test_cobro_parcial_reclasifica_solo_su_parte(): void
    {
        $invoice = $this->arInvoice(1000.00, 160.00);

        app(PaymentApplicationService::class)
            ->applyPayment($this->payment($invoice, 580.00), $invoice->fresh(), 580.00);

        $this->assertSame(-80.00, $this->balance(GlAccounts::VAT_OUTPUT_PENDING));
        $this->assertSame(-80.00, $this->balance(GlAccounts::VAT_OUTPUT));
    }

    public function test_revertir_un_cobro_regresa_el_iva_a_no_cobrado(): void
    {
        $invoice = $this->arInvoice(1000.00, 160.00);
        $service = app(PaymentApplicationService::class);

        $application = $service->applyPayment($this->payment($invoice, 580.00), $invoice->fresh(), 580.00);
        $service->unapplyPayment($application);

        $this->assertSame(-160.00, $this->balance(GlAccounts::VAT_OUTPUT_PENDING));
        $this->assertSame(0.0, $this->balance(GlAccounts::VAT_OUTPUT));
    }

    /** @return array<string, float> saldos de las cuentas que toca una compra */
    private function snapshot(): array
    {
        $codes = [GlAccounts::INVENTORY, GlAccounts::GOODS_RECEIVED, GlAccounts::VAT_INPUT_PENDING,
            GlAccounts::SUPPLIERS, GlAccounts::COST_OF_SALES];

        return array_combine($codes, array_map(fn ($c) => $this->balance($c), $codes));
    }

    /** @return array<string, float> cambio de saldo por cuenta entre dos fotos */
    private function delta(array $before, array $after): array
    {
        return array_map(fn ($c) => round($after[$c] - $before[$c], 2), array_combine(array_keys($before), array_keys($before)));
    }

    public function test_factura_de_proveedor_de_una_orden_salda_la_cuenta_puente(): void
    {
        $supplier = Contact::factory()->create();
        $purchaseOrderId = $this->purchaseOrderId($supplier);
        $before = $this->snapshot();

        // La recepcion carga Almacen contra la cuenta puente (lo hace
        // PostInventoryMovementToGL); aqui se simula con su efecto contable.
        $this->postReceipt(1000.00);

        app(APInvoiceService::class)->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $supplier->id,
            'purchaseOrderId' => $purchaseOrderId,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $d = $this->delta($before, $this->snapshot());
        $this->assertSame(1000.00, $d[GlAccounts::INVENTORY]);
        $this->assertSame(0.0, $d[GlAccounts::GOODS_RECEIVED]);
        $this->assertSame(160.00, $d[GlAccounts::VAT_INPUT_PENDING]);
        // Proveedores se abona una sola vez, por el total con IVA.
        $this->assertSame(-1160.00, $d[GlAccounts::SUPPLIERS]);
        $this->assertSame(0.0, $d[GlAccounts::COST_OF_SALES]);
    }

    public function test_factura_de_proveedor_sin_orden_va_a_gasto_con_iva_aparte(): void
    {
        $before = $this->snapshot();

        app(APInvoiceService::class)->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => Contact::factory()->create()->id,
            'currency' => 'MXN',
            'subtotal' => 500.00,
            'taxAmount' => 80.00,
            'totalAmount' => 580.00,
        ]);

        $d = $this->delta($before, $this->snapshot());
        $this->assertSame(500.00, $d[GlAccounts::COST_OF_SALES]);
        $this->assertSame(80.00, $d[GlAccounts::VAT_INPUT_PENDING]);
        $this->assertSame(-580.00, $d[GlAccounts::SUPPLIERS]);
    }

    public function test_las_cuentas_del_sistema_se_crean_bajo_su_padre_si_faltan(): void
    {
        // Se aparta la existente (puede tener movimientos) para simular un catalogo sin ella.
        Account::where('code', GlAccounts::GOODS_RECEIVED)->update(['code' => 'X-2110']);

        $account = GlAccounts::get(GlAccounts::GOODS_RECEIVED);

        $this->assertSame('Mercancía Recibida por Facturar', $account->name);
        $this->assertTrue((bool) $account->is_postable);
        $parent = Account::where('code', '2100')->first();
        if ($parent) {
            $this->assertSame($parent->id, $account->parent_id);
        }
    }

    public function test_tax_portion_es_telescopico(): void
    {
        $parts = [
            GlAccounts::taxPortion(160, 1160, 0, 333.33),
            GlAccounts::taxPortion(160, 1160, 333.33, 666.66),
            GlAccounts::taxPortion(160, 1160, 666.66, 1160),
        ];

        $this->assertSame(160.0, round(array_sum($parts), 2));
        $this->assertSame(0.0, GlAccounts::taxPortion(0, 1160, 0, 500));
    }

    private function postReceipt(float $amount): void
    {
        app(\Modules\Accounting\Services\AccountingService::class)->createJournalEntry(
            'GL',
            now()->format('Y-m-d'),
            'Entrada de prueba',
            'INV-TEST',
            [
                ['account_id' => GlAccounts::get(GlAccounts::INVENTORY)->id, 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => 'Almacen'],
                ['account_id' => GlAccounts::get(GlAccounts::GOODS_RECEIVED)->id, 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => 'Puente'],
            ]
        );
    }

    private function purchaseOrderId(Contact $supplier): int
    {
        return \Modules\Purchase\Models\PurchaseOrder::factory()->create(['contact_id' => $supplier->id])->id;
    }
}

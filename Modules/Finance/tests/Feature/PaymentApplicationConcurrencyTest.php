<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Accounting\Models\FiscalPeriod;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Services\PaymentApplicationService;
use Tests\TestCase;

/**
 * Auditoria finanzas 2026-10, punto 5: el saldo se validaba contra el modelo
 * que traia el llamador, sin bloqueo. Dos cobros con la misma factura en
 * memoria sobrepagaban, y revertir dos veces la misma aplicacion restaba el
 * abono doble. SQLite no reproduce el FOR UPDATE; estas pruebas cubren la
 * relectura dentro de la transaccion, que es lo que cierra la carrera.
 */
class PaymentApplicationConcurrencyTest extends TestCase
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

    private function invoice(float $total = 1160.00): ARInvoice
    {
        return ARInvoice::factory()->create([
            'contact_id' => Contact::factory()->customer()->create()->id,
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
    }

    private function register(ARInvoice $invoice, float $amount)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/register-payment", [
                'payment_date' => now()->format('Y-m-d'),
                'amount' => $amount,
                'forma_pago' => '03',
            ]);
    }

    public function test_stale_invoice_copy_cannot_overpay(): void
    {
        $invoice = $this->invoice(1160.00);
        $stale = ARInvoice::findOrFail($invoice->id); // copia leida antes del primer cobro

        $this->register($invoice, 1000.00)->assertOk();

        $service = app(PaymentApplicationService::class);
        $payment = \Modules\Finance\Models\Payment::query()
            ->where('contact_id', $invoice->contact_id)->latest('id')->firstOrFail()
            ->replicate(['payment_number', 'journal_entry_id']);
        $payment->payment_number = 'PAY-TEST-' . uniqid();
        $payment->applied_amount = 0;
        $payment->unapplied_amount = 1000.00;
        $payment->status = 'unapplied';
        $payment->save();

        try {
            $service->applyPayment($payment, $stale, 1000.00);
            $this->fail('Se aplico un cobro mayor al saldo con una copia vieja de la factura');
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('Se aplico', $e->getMessage());
        }

        $this->assertEquals(1000.00, (float) $invoice->fresh()->paid_amount);
    }

    public function test_application_cannot_be_reversed_twice(): void
    {
        $invoice = $this->invoice(1160.00);
        $this->register($invoice, 500.00)->assertOk();

        $application = PaymentApplication::where('ar_invoice_id', $invoice->id)
            ->whereHas('payment', fn ($q) => $q->where('contact_id', $invoice->contact_id))
            ->latest('id')->firstOrFail();
        $stale = PaymentApplication::findOrFail($application->id);

        $service = app(PaymentApplicationService::class);
        $service->unapplyPayment($application);

        $this->expectExceptionMessage('ya fue revertida');
        try {
            $service->unapplyPayment($stale);
        } finally {
            $this->assertEquals(0.0, (float) $invoice->fresh()->paid_amount);
        }
    }
}

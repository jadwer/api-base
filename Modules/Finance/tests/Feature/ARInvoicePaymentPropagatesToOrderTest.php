<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Accounting\Models\FiscalPeriod;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Services\PaymentApplicationService;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * Decision Gabino 2026-09-23: el cobro registrado en Cuentas por Cobrar
 * propaga a la orden de venta (E2E dev 18-sep, H1: la AR quedaba paid y la
 * orden seguia "no pagada").
 */
class ARInvoicePaymentPropagatesToOrderTest extends TestCase
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

    private function orderWithInvoice(float $total = 1160.00): array
    {
        $customer = Contact::factory()->customer()->create();
        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'delivered',
            'total_amount' => $total,
            'payment_status' => 'unpaid',
            'paid_at' => null,
        ]);
        $invoice = ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'sales_order_id' => $order->id,
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
        $order->updateQuietly(['ar_invoice_id' => $invoice->id, 'invoicing_status' => 'invoiced']);

        return [$order, $invoice];
    }

    private function registerPayment(ARInvoice $invoice, float $amount)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/register-payment", [
                'payment_date' => now()->format('Y-m-d'),
                'amount' => $amount,
                'forma_pago' => '03',
            ]);
    }

    public function test_full_payment_marks_the_sales_order_as_paid(): void
    {
        [$order, $invoice] = $this->orderWithInvoice(1160.00);

        $this->registerPayment($invoice, 1160.00)->assertOk();

        $order->refresh();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame($invoice->id, $order->metadata['paid_via_ar_invoice_id'] ?? null);
    }

    public function test_partial_payment_leaves_the_sales_order_unpaid(): void
    {
        [$order, $invoice] = $this->orderWithInvoice(1160.00);

        $this->registerPayment($invoice, 500.00)->assertOk();

        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_reversing_the_payment_returns_the_order_to_unpaid(): void
    {
        [$order, $invoice] = $this->orderWithInvoice(1160.00);
        $this->registerPayment($invoice, 1160.00)->assertOk();
        $this->assertSame('paid', $order->fresh()->payment_status);

        $application = PaymentApplication::where('ar_invoice_id', $invoice->id)->firstOrFail();
        app(PaymentApplicationService::class)->unapplyPayment($application);

        $order->refresh();
        $this->assertSame('posted', $invoice->fresh()->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertNull($order->paid_at);
    }
}

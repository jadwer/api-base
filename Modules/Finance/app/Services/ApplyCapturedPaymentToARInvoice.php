<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Log;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\Payment;

/**
 * Aplica a una AR el cobro Stripe ya capturado de su orden de venta, por el
 * MISMO servicio auditado que usa el dashboard (ARPayment con folio +
 * PaymentApplication + asiento DR banco / CR clientes + REP).
 *
 * Extraido de CreateARInvoiceForSalesOrder (2026-09-23) porque ahora hay dos
 * momentos en que una AR puede nacer con la orden ya pagada: al capturar el
 * pago en la tienda (CreateARInvoiceOnPaymentCaptured) y, si aquello fallo o
 * estaba apagado, al entregar.
 *
 * Un fallo aqui (periodo cerrado, forma de pago sin sembrar, cuenta faltante)
 * NO tumba la facturacion: la AR queda posted sin aplicar, se anota en
 * invoicing_notes y el cobro se registra manual desde el dashboard.
 */
class ApplyCapturedPaymentToARInvoice
{
    public function __construct(
        private ARInvoicePaymentRegistrationService $registration,
    ) {}

    public function apply(object $salesOrder, ARInvoice $arInvoice, ?PaymentTransaction $transaction = null, string $comments = 'Cobro Stripe aplicado automaticamente'): ?Payment
    {
        $transaction ??= PaymentTransaction::where('sales_order_id', $salesOrder->id)
            ->where('status', 'captured')
            ->latest('captured_at')
            ->first();

        if (! $transaction) {
            Log::warning('Orden sin transaccion captured; no se aplica cobro a la AR', [
                'sales_order_id' => $salesOrder->id,
                'ar_invoice_id' => $arInvoice->id,
            ]);

            return null;
        }

        if ($arInvoice->status === 'paid') {
            return null;
        }

        try {
            $payment = $this->registration->register($arInvoice, [
                'payment_date' => ($transaction->captured_at ?? $salesOrder->paid_at ?? now())->format('Y-m-d'),
                'amount' => (float) $arInvoice->total_amount,
                'forma_pago' => (string) config('billing.stripe_forma_pago', '04'),
                'reference' => $transaction->payment_intent_id,
                'comments' => $comments,
                'bank_account_id' => config('billing.stripe_bank_account_id'),
            ]);

            Log::info('Cobro Stripe aplicado a la AR', [
                'sales_order_id' => $salesOrder->id,
                'ar_invoice_id' => $arInvoice->id,
                'ar_payment_id' => $payment->id,
                'payment_intent_id' => $transaction->payment_intent_id,
            ]);

            return $payment;
        } catch (\Throwable $e) {
            Log::error('No se pudo aplicar el cobro Stripe a la AR; aplicar manual desde dashboard', [
                'sales_order_id' => $salesOrder->id,
                'ar_invoice_id' => $arInvoice->id,
                'error' => $e->getMessage(),
            ]);
            $salesOrder->updateQuietly([
                'invoicing_notes' => trim(($salesOrder->invoicing_notes ?? '') .
                    ' | Cobro Stripe NO aplicado a la AR (' . $e->getMessage() . '): registrar manual.'),
            ]);

            return null;
        }
    }
}

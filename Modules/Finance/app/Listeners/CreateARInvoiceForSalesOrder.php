<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Services\ARInvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * CreateARInvoiceForSalesOrder
 *
 * R3 (diseno post-refactor): punto UNICO de creacion de la ARInvoice por evento.
 * Reemplaza a SalesOrderDeliveredListener y SalesOrderCompletedListener, que
 * duplicaban la misma logica con guards ligeramente distintos. Escucha:
 * - SalesOrderDelivered (dashboard: entrega por remision o por endpoint de status).
 * - SalesOrderCompleted (flujo CheckoutSession de ecommerce y EventReplayService;
 *   se retirara cuando ecommerce converja a SalesOrderDelivered, R8 del diseno).
 *
 * El interruptor sales.auto_invoice_on_delivery aplica a AMBOS caminos (antes solo
 * al de entrega; se unifica para tener un solo switch de auto-facturacion).
 *
 * Idempotente por ar_invoice_id / invoiceExistsForOrder: reenvios y dobles disparos
 * no duplican factura. Un fallo NO bloquea el flujo que disparo el evento; queda en
 * invoicing_notes para facturar manualmente (o via EventReplayService).
 */
class CreateARInvoiceForSalesOrder
{
    public function __construct(
        private ARInvoiceService $arInvoiceService
    ) {}

    public function handle(object $event): void
    {
        $salesOrder = $event->salesOrder;

        if (!config('sales.auto_invoice_on_delivery', true)) {
            Log::info('Auto-invoicing disabled, skipping AR Invoice creation', [
                'sales_order_id' => $salesOrder->id,
                'event' => get_class($event),
            ]);
            return;
        }

        // Idempotencia: ya facturada (por cualquiera de los caminos).
        if ($salesOrder->ar_invoice_id || $this->arInvoiceService->invoiceExistsForOrder($salesOrder->id)) {
            Log::info('SalesOrder already has AR Invoice', [
                'sales_order_id' => $salesOrder->id,
                'ar_invoice_id' => $salesOrder->ar_invoice_id,
            ]);
            return;
        }

        // Precondiciones de negocio (status delivered|completed, cliente activo).
        if (!$this->arInvoiceService->canGenerateInvoice($salesOrder)) {
            Log::info('SalesOrder does not meet conditions for AR Invoice generation', [
                'sales_order_id' => $salesOrder->id,
                'status' => $salesOrder->status,
                'event' => get_class($event),
            ]);
            return;
        }

        try {
            $arInvoice = $this->arInvoiceService->createFromSalesOrder($salesOrder);

            Log::info('AR Invoice created from SalesOrder', [
                'sales_order_id' => $salesOrder->id,
                'sales_order_number' => $salesOrder->order_number,
                'ar_invoice_id' => $arInvoice->id,
                'ar_invoice_number' => $arInvoice->invoice_number,
                'total_amount' => $arInvoice->total_amount,
                'event' => get_class($event),
            ]);

            // DESIGN_ECOMMERCE_PAGO_STOCK (H-C): si la orden ya fue pagada por
            // Stripe, aplicar el cobro a la AR recien nacida por el MISMO
            // servicio auditado que usa el dashboard (ARPayment con folio +
            // PaymentApplication + asiento DR banco / CR clientes + REP). La
            // factura queda paid balance 0 sin camino contable nuevo.
            $this->applyStripePaymentIfPaid($salesOrder, $arInvoice);

        } catch (\Exception $e) {
            Log::error('Failed to create AR Invoice from SalesOrder', [
                'sales_order_id' => $salesOrder->id,
                'error' => $e->getMessage(),
                'event' => get_class($event),
            ]);

            // No bloquear el flujo: la factura puede crearse manualmente o via replay.
            $salesOrder->updateQuietly([
                'invoicing_notes' => 'Failed to create AR Invoice: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Aplica el cobro Stripe capturado a la AR recien creada.
     *
     * Guards: orden paid + transaccion captured ligada. Un fallo aqui (periodo
     * cerrado, forma de pago sin sembrar, cuenta faltante) NO tumba la
     * facturacion: la AR queda posted sin aplicar, se anota en invoicing_notes
     * y el cobro se registra manual desde el dashboard (mismo patron de
     * recuperacion del resto del listener).
     */
    private function applyStripePaymentIfPaid(object $salesOrder, object $arInvoice): void
    {
        if ($salesOrder->payment_status !== 'paid') {
            return;
        }

        $transaction = \Modules\Billing\Models\PaymentTransaction::where('sales_order_id', $salesOrder->id)
            ->where('status', 'captured')
            ->latest('captured_at')
            ->first();

        if (!$transaction) {
            Log::warning('Orden marcada paid sin transaccion captured; no se aplica cobro', [
                'sales_order_id' => $salesOrder->id,
            ]);
            return;
        }

        try {
            $payment = app(\Modules\Finance\Services\ARInvoicePaymentRegistrationService::class)
                ->register($arInvoice, [
                    'payment_date' => ($salesOrder->paid_at ?? now())->format('Y-m-d'),
                    'amount' => (float) $arInvoice->total_amount,
                    'forma_pago' => (string) config('billing.stripe_forma_pago', '04'),
                    'reference' => $transaction->payment_intent_id,
                    'comments' => 'Cobro Stripe aplicado automaticamente al facturar la entrega',
                    'bank_account_id' => config('billing.stripe_bank_account_id'),
                ]);

            Log::info('Cobro Stripe aplicado a la AR', [
                'sales_order_id' => $salesOrder->id,
                'ar_invoice_id' => $arInvoice->id,
                'ar_payment_id' => $payment->id,
                'payment_intent_id' => $transaction->payment_intent_id,
            ]);
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
        }
    }
}

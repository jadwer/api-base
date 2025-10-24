<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Models\ARInvoice;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaymentApplicationService
 *
 * Maneja la aplicación de pagos a AR Invoices con GL posting automático
 *
 * Business Rules:
 * - Un Payment puede aplicarse a múltiples AR Invoices (pago parcial/total)
 * - Valida que payment amount no exceda invoice balance
 * - Valida que payment amount no exceda unapplied balance
 * - Crea JournalEntry automático: DR Banco, CR Clientes
 * - Actualiza paid_amount en invoice y applied_amount en payment
 */
class PaymentApplicationService
{
    public function __construct(
        private AccountingService $accountingService,
        private ARInvoiceService $arInvoiceService
    ) {}

    /**
     * Aplicar payment a AR invoice
     *
     * @param Payment $payment Payment a aplicar
     * @param ARInvoice $invoice Invoice a la que aplicar el pago
     * @param float $amount Monto a aplicar
     * @return PaymentApplication
     * @throws \Exception Si las validaciones fallan
     */
    public function applyPayment(Payment $payment, ARInvoice $invoice, float $amount): PaymentApplication
    {
        return DB::transaction(function () use ($payment, $invoice, $amount) {
            // 1. Validaciones de negocio
            $this->validatePaymentApplication($payment, $invoice, $amount);

            // 2. Crear PaymentApplication
            $application = PaymentApplication::create([
                'payment_id' => $payment->id,
                'ar_invoice_id' => $invoice->id,
                'amount' => $amount,
                'application_date' => now(),
                'is_active' => true,
            ]);

            // 3. Actualizar invoice paid_amount
            $invoice->increment('paid_amount', $amount);

            // Actualizar status si está completamente pagada
            if ($this->arInvoiceService->isFullyPaid($invoice)) {
                $invoice->update(['status' => 'paid']);
            } else {
                $invoice->update(['status' => 'partial']);
            }

            // 4. Actualizar payment applied_amount y unapplied_amount
            $payment->increment('applied_amount', $amount);
            $newUnappliedAmount = $payment->amount - $payment->applied_amount;
            $payment->update([
                'unapplied_amount' => $newUnappliedAmount,
                'status' => $newUnappliedAmount <= 0.01 ? 'applied' : 'partial',
            ]);

            // 5. Crear GL entry para el payment (si aún no tiene)
            // Solo crear GL entry en el primer application del payment
            if ($payment->paymentApplications()->count() === 1 && !$payment->journal_entry_id) {
                $this->createPaymentGLEntry($payment);
            }

            Log::info("Payment applied to AR Invoice", [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $amount,
                'application_id' => $application->id,
            ]);

            return $application->fresh(['payment', 'arInvoice']);
        });
    }

    /**
     * Revertir aplicación de pago (unapply)
     *
     * @param PaymentApplication $application
     * @return bool
     * @throws \Exception Si falla la reversión
     */
    public function unapplyPayment(PaymentApplication $application): bool
    {
        return DB::transaction(function () use ($application) {
            $payment = $application->payment;
            $invoice = $application->aRInvoice;
            $amount = $application->amount;

            // 1. Revertir invoice paid_amount
            $invoice->decrement('paid_amount', $amount);

            // Actualizar status
            if ($invoice->paid_amount <= 0.01) {
                $invoice->update(['status' => 'posted']);
            } else {
                $invoice->update(['status' => 'partial']);
            }

            // 2. Revertir payment applied_amount
            $payment->decrement('applied_amount', $amount);
            $newUnappliedAmount = $payment->amount - $payment->applied_amount;
            $payment->update([
                'unapplied_amount' => $newUnappliedAmount,
                'status' => $newUnappliedAmount >= ($payment->amount - 0.01) ? 'unapplied' : 'partial',
            ]);

            // 3. Soft delete la application
            $application->update(['is_active' => false]);

            Log::info("Payment application reversed", [
                'application_id' => $application->id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);

            return true;
        });
    }

    /**
     * Validar que la aplicación de pago sea válida
     *
     * @param Payment $payment
     * @param ARInvoice $invoice
     * @param float $amount
     * @return void
     * @throws \Exception Si alguna validación falla
     */
    private function validatePaymentApplication(Payment $payment, ARInvoice $invoice, float $amount): void
    {
        // 1. Validar que el monto sea positivo
        if ($amount <= 0) {
            throw new \Exception("Payment amount must be greater than zero");
        }

        // 2. Validar que el payment no exceda el balance pendiente de la invoice
        $remainingBalance = $this->arInvoiceService->calculateRemainingBalance($invoice);
        if ($amount > $remainingBalance + 0.01) { // Tolerancia de 1 centavo
            throw new \Exception(
                "Payment amount ($amount) exceeds invoice remaining balance ($remainingBalance)"
            );
        }

        // 3. Validar que el payment no exceda el saldo no aplicado del payment
        $unappliedAmount = $payment->amount - $payment->applied_amount;
        if ($amount > $unappliedAmount + 0.01) {
            throw new \Exception(
                "Payment amount ($amount) exceeds unapplied payment balance ($unappliedAmount)"
            );
        }

        // 4. Validar que el customer del payment coincida con el de la invoice
        if ($payment->customer_id !== $invoice->customer_id) {
            throw new \Exception(
                "Payment customer ({$payment->customer_id}) does not match invoice customer ({$invoice->customer_id})"
            );
        }

        // 5. Validar que la invoice no esté ya completamente pagada
        if ($this->arInvoiceService->isFullyPaid($invoice)) {
            throw new \Exception("Invoice is already fully paid");
        }

        // 6. Validar que ambos estén activos
        if (!$payment->is_active || !$invoice->is_active) {
            throw new \Exception("Payment or Invoice is not active");
        }
    }

    /**
     * Crear GL entry para el payment
     *
     * JournalEntry: DR Banco, CR Clientes
     *
     * @param Payment $payment
     * @return void
     * @throws \Exception Si falla la creación del GL entry
     */
    private function createPaymentGLEntry(Payment $payment): void
    {
        // Validar cuentas GL
        $bankAccount = Account::where('code', '1020')->where('is_postable', true)->first();
        $customerAccount = Account::where('code', '1100')->where('is_postable', true)->first();

        if (!$bankAccount) {
            throw new \Exception('GL Account for Bank (1020) not found or not postable.');
        }

        if (!$customerAccount) {
            throw new \Exception('GL Account for Customers (1100) not found or not postable.');
        }

        try {
            $journalEntry = $this->accountingService->createJournalEntry(
                journalCode: 'CR', // Cash Receipts
                entryDate: $payment->payment_date,
                description: "Payment #{$payment->payment_number} - Customer #{$payment->customer_id}",
                reference: $payment->payment_number,
                lines: [
                    [
                        'account_id' => $bankAccount->id,
                        'debit_amount' => $payment->amount,
                        'credit_amount' => 0,
                        'description' => "Bank - Payment #{$payment->payment_number}",
                    ],
                    [
                        'account_id' => $customerAccount->id,
                        'debit_amount' => 0,
                        'credit_amount' => $payment->amount,
                        'description' => "A/R - Payment #{$payment->payment_number}",
                    ],
                ]
            );

            $payment->update(['journal_entry_id' => $journalEntry->id]);

            Log::info("Payment GL entry created", [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'journal_entry_id' => $journalEntry->id,
                'amount' => $payment->amount,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create GL entry for Payment", [
                'payment_number' => $payment->payment_number,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to create GL entry: " . $e->getMessage());
        }
    }

    /**
     * Obtener todas las applications de un payment
     *
     * @param Payment $payment
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPaymentApplications(Payment $payment)
    {
        return $payment->paymentApplications()
            ->where('is_active', true)
            ->with('arInvoice')
            ->get();
    }

    /**
     * Obtener total aplicado de un payment
     *
     * @param Payment $payment
     * @return float
     */
    public function getTotalApplied(Payment $payment): float
    {
        return $payment->paymentApplications()
            ->where('is_active', true)
            ->sum('amount');
    }
}

<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\APInvoice;
use Modules\Finance\Events\APInvoicePosted;
use Modules\Accounting\Support\GlAccounts;
use Modules\Accounting\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * APInvoiceService
 *
 * Maneja la creación de AP Invoices (Facturas por Pagar a Proveedores) con GL posting automático
 *
 * Business Rules:
 * - Genera invoice_number secuencial (AP-XXXXXX)
 * - Crea JournalEntry automático: DR Gastos, CR Proveedores
 * - Valida que las cuentas GL existan antes de posting
 * - Todo en transacción DB para garantizar consistencia
 */
class APInvoiceService
{
    public function __construct(
        private AccountingService $accountingService,
        private APInvoiceReconciliationService $reconciliationService
    ) {}

    /**
     * Crear AP Invoice con GL posting automático
     *
     * @param array $data Datos de la invoice
     * @return APInvoice Invoice creada con journalEntry cargado
     * @throws \Exception Si las cuentas GL no existen o si falla el posting
     */
    public function createInvoice(array $data): APInvoice
    {
        return DB::transaction(function () use ($data) {
            // 1. Cuentas GL (auditoria finanzas 2026-10, puntos 1 y 2). La mercancia
            // de una orden ya entro a Almacen contra "Mercancia recibida por
            // facturar" al recibirla; la factura salda esa cuenta puente (antes
            // cargaba Costo de Ventas y 2101 quedaba abonado dos veces). El IVA va
            // a acreditable pendiente de pago, no al costo.
            $debitAccount = !empty($data['purchaseOrderId'])
                ? GlAccounts::get(GlAccounts::GOODS_RECEIVED)
                : GlAccounts::get(GlAccounts::COST_OF_SALES);
            $supplierAccount = GlAccounts::get(GlAccounts::SUPPLIERS);
            $taxAmount = round((float) ($data['taxAmount'] ?? 0), 2);
            $netAmount = round((float) $data['totalAmount'] - $taxAmount, 2);
            $vatPendingAccount = $taxAmount > 0 ? GlAccounts::get(GlAccounts::VAT_INPUT_PENDING) : null;

            // 2. Generar invoice number secuencial
            $invoiceNumber = $this->generateInvoiceNumber();

            // 3. Crear AP Invoice
            $invoice = APInvoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoiceDate'],
                'due_date' => $data['dueDate'],
                'contact_id' => $data['contactId'],
                'purchase_order_id' => $data['purchaseOrderId'] ?? null,
                'currency' => $data['currency'] ?? 'MXN',
                'subtotal' => $data['subtotal'],
                'tax_amount' => $data['taxAmount'],
                'total_amount' => $data['totalAmount'],
                'paid_amount' => 0,
                'status' => 'posted', // Cambia a 'posted' al crear el GL entry
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'is_active' => true,
            ]);

            // 4. Crear JournalEntry con AccountingService
            try {
                $journalEntry = $this->accountingService->createJournalEntry(
                    journalCode: 'AP',
                    entryDate: $data['invoiceDate'],
                    description: "AP Invoice #{$invoiceNumber} - Contact #{$data['contactId']}",
                    reference: $invoiceNumber,
                    lines: [
                        [
                            'account_id' => $debitAccount->id,
                            'debit_amount' => $netAmount,
                            'credit_amount' => 0,
                            'description' => "Expense - Invoice #{$invoiceNumber}",
                        ],
                        ...($vatPendingAccount ? [[
                            'account_id' => $vatPendingAccount->id,
                            'debit_amount' => $taxAmount,
                            'credit_amount' => 0,
                            'description' => "IVA acreditable pendiente de pago - Invoice #{$invoiceNumber}",
                        ]] : []),
                        [
                            'account_id' => $supplierAccount->id,
                            'debit_amount' => 0,
                            'credit_amount' => $data['totalAmount'],
                            'description' => "A/P - Invoice #{$invoiceNumber}",
                        ],
                    ]
                );

                // 5. Vincular journal entry a la invoice
                $invoice->update(['journal_entry_id' => $journalEntry->id]);

                Log::info("AP Invoice created with GL posting", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoiceNumber,
                    'journal_entry_id' => $journalEntry->id,
                    'amount' => $data['totalAmount'],
                ]);

                // 6. Dispatch event for listeners (status sync, etc.)
                event(new APInvoicePosted($invoice));

                // 7. PU-M002: Auto-reconcile if linked to Purchase Order
                if ($invoice->purchase_order_id && config('purchase.auto_reconcile_invoices', true)) {
                    try {
                        $this->reconciliationService->reconcileInvoice($invoice, $data['userId'] ?? null);
                        Log::info("AP Invoice auto-reconciled with Purchase Order", [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoiceNumber,
                            'purchase_order_id' => $invoice->purchase_order_id,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("Failed to auto-reconcile AP Invoice", [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoiceNumber,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't throw - reconciliation can be done manually later
                    }
                }

            } catch (\Exception $e) {
                Log::error("Failed to create GL entry for AP Invoice", [
                    'invoice_number' => $invoiceNumber,
                    'error' => $e->getMessage(),
                ]);
                throw new \Exception("No se pudo crear la póliza contable: " . $e->getMessage());
            }

            // 8. Retornar invoice con relaciones cargadas
            return $invoice->fresh(['journalEntry', 'contact', 'purchaseOrder']);
        });
    }

    /**
     * Actualizar AP Invoice
     *
     * NOTA: No actualiza el GL entry existente. Para eso se debe crear un reversal + nuevo entry.
     * Esta versión solo permite actualizar campos no-financieros (notes, metadata, due_date)
     *
     * @param APInvoice $invoice
     * @param array $data
     * @return APInvoice
     */
    public function updateInvoice(APInvoice $invoice, array $data): APInvoice
    {
        // Solo permitir actualización de campos no-financieros si ya tiene GL entry
        if ($invoice->journal_entry_id) {
            $allowedFields = ['due_date', 'notes', 'metadata'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (count($updateData) === 0) {
                throw new \Exception('No se pueden modificar los importes después de contabilizar. Registra una reversa.');
            }
        } else {
            $updateData = $data;
        }

        $invoice->update($updateData);

        return $invoice->fresh();
    }

    /**
     * Generar invoice number secuencial
     *
     * Formato: AP-XXXXXX (6 dígitos)
     *
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        $lastInvoice = APInvoice::lockForUpdate()->orderBy('id', 'desc')->first();
        $nextNumber = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, 3)) + 1 : 1;
        return 'AP-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular remaining balance (saldo pendiente)
     *
     * @param APInvoice $invoice
     * @return float
     */
    public function calculateRemainingBalance(APInvoice $invoice): float
    {
        return $invoice->total_amount - $invoice->paid_amount;
    }

    /**
     * Verificar si invoice está completamente pagada
     *
     * @param APInvoice $invoice
     * @return bool
     */
    public function isFullyPaid(APInvoice $invoice): bool
    {
        return $this->calculateRemainingBalance($invoice) <= 0.01; // Tolerancia de 1 centavo
    }

    /**
     * Verificar si invoice está vencida
     *
     * @param APInvoice $invoice
     * @return bool
     */
    public function isOverdue(APInvoice $invoice): bool
    {
        if ($this->isFullyPaid($invoice)) {
            return false;
        }

        return now()->isAfter($invoice->due_date);
    }
}

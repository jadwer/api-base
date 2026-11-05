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

    /**
     * Captura la factura del proveedor sobre una CxP nacida de la recepcion
     * (decision de la contadora: la orden de compra no lleva IVA; el impuesto
     * sale de la factura del proveedor). Agrega el IVA a la CxP y lo contabiliza
     * como acreditable pendiente de pago: Dr 1110 / Cr 2101. El subtotal debe
     * coincidir con lo recibido; una diferencia se corrige en la orden.
     *
     * @param array{supplier_invoice_number: string, supplier_uuid?: ?string, subtotal: float|string, tax_amount: float|string} $data
     * @throws \DomainException Dato del usuario que no cuadra (mensaje para pantalla)
     * @throws \Exception Periodo fiscal cerrado o diario contable faltante
     */
    public function registerSupplierInvoice(APInvoice $invoice, array $data, ?int $userId = null): APInvoice
    {
        return DB::transaction(function () use ($invoice, $data, $userId) {
            /** @var APInvoice $invoice */
            $invoice = APInvoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if (!empty(($invoice->metadata ?? [])['supplier_invoice'])) {
                throw new \DomainException('La factura del proveedor ya se capturó en esta cuenta por pagar.');
            }
            if (!in_array($invoice->status, ['posted', 'overdue'], true) || (float) $invoice->paid_amount > 0) {
                throw new \DomainException('Solo se captura la factura del proveedor en una cuenta por pagar contabilizada y sin pagos.');
            }

            $subtotal = round((float) $data['subtotal'], 2);
            $tax = round((float) $data['tax_amount'], 2);
            $expected = round((float) $invoice->subtotal, 2);
            if (abs($subtotal - $expected) > 0.01) {
                throw new \DomainException(sprintf(
                    'El subtotal de la factura del proveedor ($%s) no coincide con lo recibido ($%s). Corrige los precios o cantidades de la orden de compra antes de capturarla.',
                    number_format($subtotal, 2), number_format($expected, 2)
                ));
            }
            if ($tax < 0 || $tax > round($subtotal * 0.16, 2) + 0.01) {
                throw new \DomainException('El IVA debe estar entre $0 y el 16 % del subtotal.');
            }

            if ($tax > 0) {
                $this->accountingService->createJournalEntry(
                    journalCode: 'AP',
                    entryDate: now()->format('Y-m-d'),
                    description: "IVA de la factura del proveedor {$data['supplier_invoice_number']} - CxP {$invoice->invoice_number}",
                    reference: $invoice->invoice_number,
                    lines: [
                        ['account_id' => GlAccounts::get(GlAccounts::VAT_INPUT_PENDING)->id, 'debit_amount' => $tax, 'credit_amount' => 0, 'description' => "IVA acreditable pendiente - {$invoice->invoice_number}"],
                        ['account_id' => GlAccounts::get(GlAccounts::SUPPLIERS)->id, 'debit_amount' => 0, 'credit_amount' => $tax, 'description' => "IVA de la factura {$data['supplier_invoice_number']}"],
                    ]
                );
            }

            $metadata = $invoice->metadata ?? [];
            $metadata['supplier_invoice'] = [
                'number' => $data['supplier_invoice_number'],
                'uuid' => $data['supplier_uuid'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'captured_at' => now()->toIso8601String(),
                'captured_by' => $userId,
            ];
            $invoice->forceFill([
                'tax_amount' => $tax,
                'total_amount' => round($expected + $tax, 2),
                'metadata' => $metadata,
            ])->save();

            Log::info('Factura del proveedor capturada en CxP', [
                'ap_invoice_id' => $invoice->id,
                'supplier_invoice' => $data['supplier_invoice_number'],
                'tax_amount' => $tax,
            ]);

            return $invoice->fresh();
        });
    }
}

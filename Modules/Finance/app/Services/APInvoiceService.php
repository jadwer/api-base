<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\APInvoice;
use Modules\Accounting\Models\Account;
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
        private AccountingService $accountingService
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
            // 1. Validar que existan las cuentas GL requeridas
            $expenseAccount = Account::where('code', '5100')->where('is_postable', true)->first();
            $supplierAccount = Account::where('code', '2100')->where('is_postable', true)->first();

            if (!$expenseAccount) {
                throw new \Exception('GL Account for Expenses (5100) not found or not postable. Please configure the chart of accounts.');
            }

            if (!$supplierAccount) {
                throw new \Exception('GL Account for Suppliers/Accounts Payable (2100) not found or not postable. Please configure the chart of accounts.');
            }

            // 2. Generar invoice number secuencial
            $invoiceNumber = $this->generateInvoiceNumber();

            // 3. Crear AP Invoice
            $invoice = APInvoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoiceDate'],
                'due_date' => $data['dueDate'],
                'contact_id' => $data['contactId'],
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
                            'account_id' => $expenseAccount->id,
                            'debit_amount' => $data['totalAmount'],
                            'credit_amount' => 0,
                            'description' => "Expense - Invoice #{$invoiceNumber}",
                        ],
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

            } catch (\Exception $e) {
                Log::error("Failed to create GL entry for AP Invoice", [
                    'invoice_number' => $invoiceNumber,
                    'error' => $e->getMessage(),
                ]);
                throw new \Exception("Failed to create GL entry: " . $e->getMessage());
            }

            // 6. Retornar invoice con relaciones cargadas
            return $invoice->fresh(['journalEntry', 'contact']);
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
                throw new \Exception('Cannot update financial fields after GL posting. Create a reversal entry instead.');
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
        $lastInvoice = APInvoice::orderBy('id', 'desc')->first();
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

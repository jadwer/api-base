<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Events\ARInvoicePosted;
use Modules\Finance\Services\CreditManagementService;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\AccountingService;
use Modules\Contacts\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ARInvoiceService
 *
 * Maneja la creación de AR Invoices (Facturas por Cobrar) con GL posting automático
 *
 * Business Rules:
 * - Genera invoice_number secuencial (AR-XXXXXX)
 * - Crea JournalEntry automático: DR Clientes, CR Ingresos
 * - Valida que las cuentas GL existan antes de posting
 * - Todo en transacción DB para garantizar consistencia
 */
class ARInvoiceService
{
    public function __construct(
        private AccountingService $accountingService,
        private CreditManagementService $creditManagementService
    ) {}

    /**
     * Crear AR Invoice con GL posting automático
     *
     * @param array $data Datos de la invoice
     * @return ARInvoice Invoice creada con journalEntry cargado
     * @throws \Exception Si las cuentas GL no existen o si falla el posting
     */
    public function createInvoice(array $data): ARInvoice
    {
        return DB::transaction(function () use ($data) {
            // 0. Validate customer credit (if enabled)
            if (config('finance.credit_validation_enabled', true)) {
                $contact = Contact::findOrFail($data['contactId']);
                $this->creditManagementService->validateCustomerCredit($contact, $data['totalAmount']);
            }

            // 1. Validar que existan las cuentas GL requeridas
            // 1104 = Clientes (cuenta postable bajo 1100 Activo Circulante)
            // 4101 = Ventas (cuenta postable bajo 4100 Ingresos por Ventas)
            $customerAccount = Account::where('code', '1104')->where('is_postable', true)->first();
            $revenueAccount = Account::where('code', '4101')->where('is_postable', true)->first();

            if (!$customerAccount) {
                throw new \Exception('GL Account for Customers (1104) not found or not postable. Please configure the chart of accounts.');
            }

            if (!$revenueAccount) {
                throw new \Exception('GL Account for Revenue (4101) not found or not postable. Please configure the chart of accounts.');
            }

            // 2. Generar invoice number secuencial
            $invoiceNumber = $this->generateInvoiceNumber();

            // 3. Crear AR Invoice
            $invoice = ARInvoice::create([
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
                    journalCode: 'AR',
                    entryDate: $data['invoiceDate'],
                    description: "AR Invoice #{$invoiceNumber} - Contact #{$data['contactId']}",
                    reference: $invoiceNumber,
                    lines: [
                        [
                            'account_id' => $customerAccount->id,
                            'debit_amount' => $data['totalAmount'],
                            'credit_amount' => 0,
                            'description' => "A/R - Invoice #{$invoiceNumber}",
                        ],
                        [
                            'account_id' => $revenueAccount->id,
                            'debit_amount' => 0,
                            'credit_amount' => $data['totalAmount'],
                            'description' => "Revenue - Invoice #{$invoiceNumber}",
                        ],
                    ]
                );

                // 5. Vincular journal entry a la invoice
                $invoice->update(['journal_entry_id' => $journalEntry->id]);

                Log::info("AR Invoice created with GL posting", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoiceNumber,
                    'journal_entry_id' => $journalEntry->id,
                    'amount' => $data['totalAmount'],
                ]);

                // 6. Dispatch event for listeners (status sync, etc.)
                event(new ARInvoicePosted($invoice));

            } catch (\Exception $e) {
                // Si falla el GL posting, loguear y re-lanzar
                Log::error("Failed to create GL entry for AR Invoice", [
                    'invoice_number' => $invoiceNumber,
                    'error' => $e->getMessage(),
                ]);
                throw new \Exception("Failed to create GL entry: " . $e->getMessage());
            }

            // 7. Retornar invoice con relaciones cargadas
            return $invoice->fresh(['journalEntry', 'contact']);
        });
    }

    /**
     * Create AR Invoice from Sales Order
     *
     * SA-M001: Automatically generates an AR Invoice when a Sales Order is delivered
     *
     * @param \Modules\Sales\Models\SalesOrder $salesOrder
     * @return ARInvoice
     * @throws \Exception
     */
    public function createFromSalesOrder($salesOrder): ARInvoice
    {
        // Validate sales order is delivered
        if ($salesOrder->status !== 'delivered') {
            throw new \Exception("Sales Order #{$salesOrder->order_number} must be delivered before invoicing.");
        }

        // Check if invoice already exists for this order
        if ($this->invoiceExistsForOrder($salesOrder->id)) {
            throw new \Exception("AR Invoice already exists for Sales Order #{$salesOrder->order_number}.");
        }

        // Validate customer is active
        if (!$salesOrder->contact || !$salesOrder->contact->isActive()) {
            throw new \Exception("Customer must be active to create invoice.");
        }

        // Calculate due date based on payment terms (default 30 days)
        $paymentTerms = $salesOrder->contact->payment_terms ?? 30;
        $dueDate = now()->addDays($paymentTerms);

        // Prepare data for invoice creation
        $invoiceData = [
            'invoiceDate' => now(),
            'dueDate' => $dueDate,
            'contactId' => $salesOrder->contact_id,
            'currency' => 'MXN',
            'subtotal' => $salesOrder->total_amount - ($salesOrder->discount_total ?? 0),
            'taxAmount' => 0, // TODO: Calculate tax if needed
            'totalAmount' => $salesOrder->total_amount,
            'notes' => "Auto-generated from Sales Order #{$salesOrder->order_number}",
            'metadata' => [
                'auto_generated' => true,
                'generated_at' => now()->toDateTimeString(),
                'source_order' => $salesOrder->order_number,
                'sales_order_id' => $salesOrder->id,
            ],
        ];

        // Create invoice using existing service method
        $arInvoice = $this->createInvoice($invoiceData);

        // Update sales order with invoice reference
        $salesOrder->update([
            'ar_invoice_id' => $arInvoice->id,
            'invoicing_status' => 'invoiced',
        ]);

        Log::info('AR Invoice auto-created from Sales Order', [
            'invoice_id' => $arInvoice->id,
            'invoice_number' => $arInvoice->invoice_number,
            'sales_order_id' => $salesOrder->id,
            'sales_order_number' => $salesOrder->order_number,
            'amount' => $arInvoice->total_amount,
        ]);

        return $arInvoice;
    }

    /**
     * Check if an AR Invoice already exists for a given Sales Order
     *
     * @param int $salesOrderId
     * @return bool
     */
    public function invoiceExistsForOrder(int $salesOrderId): bool
    {
        return ARInvoice::where('sales_order_id', $salesOrderId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Determine if an invoice can be generated for a Sales Order
     *
     * @param \Modules\Sales\Models\SalesOrder $salesOrder
     * @return bool
     */
    public function canGenerateInvoice($salesOrder): bool
    {
        // Must be delivered
        if ($salesOrder->status !== 'delivered') {
            return false;
        }

        // Must not already have an invoice
        if ($this->invoiceExistsForOrder($salesOrder->id)) {
            return false;
        }

        // Customer must be active
        if (!$salesOrder->contact || !$salesOrder->contact->isActive()) {
            return false;
        }

        return true;
    }

    /**
     * Actualizar AR Invoice
     *
     * NOTA: No actualiza el GL entry existente. Para eso se debe crear un reversal + nuevo entry.
     * Esta versión solo permite actualizar campos no-financieros (notes, metadata, due_date)
     *
     * @param ARInvoice $invoice
     * @param array $data
     * @return ARInvoice
     */
    public function updateInvoice(ARInvoice $invoice, array $data): ARInvoice
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
     * Formato: AR-XXXXXX (6 dígitos)
     *
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        $lastInvoice = ARInvoice::orderBy('id', 'desc')->first();
        $nextNumber = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, 3)) + 1 : 1;
        return 'AR-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular remaining balance (saldo pendiente)
     *
     * @param ARInvoice $invoice
     * @return float
     */
    public function calculateRemainingBalance(ARInvoice $invoice): float
    {
        return $invoice->total_amount - $invoice->paid_amount;
    }

    /**
     * Verificar si invoice está completamente pagada
     *
     * @param ARInvoice $invoice
     * @return bool
     */
    public function isFullyPaid(ARInvoice $invoice): bool
    {
        return $this->calculateRemainingBalance($invoice) <= 0.01; // Tolerancia de 1 centavo
    }

    /**
     * Verificar si invoice está vencida
     *
     * @param ARInvoice $invoice
     * @return bool
     */
    public function isOverdue(ARInvoice $invoice): bool
    {
        if ($this->isFullyPaid($invoice)) {
            return false;
        }

        return now()->isAfter($invoice->due_date);
    }
}

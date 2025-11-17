<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\APInvoice;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * APInvoiceReconciliationService
 *
 * PU-M002: Reconciles AP Invoices against Purchase Orders with tolerance validation
 */
class APInvoiceReconciliationService
{
    /**
     * Reconcile an AP Invoice against its Purchase Order
     *
     * Validates:
     * - Purchase Order exists and is approved
     * - Invoice total doesn't exceed PO total (with tolerance)
     * - Unit price variance is within acceptable range
     *
     * @param APInvoice $apInvoice
     * @param int|null $userId User performing the reconciliation
     * @return array Reconciliation results
     */
    public function reconcileInvoice(APInvoice $apInvoice, ?int $userId = null): array
    {
        Log::info('Starting AP Invoice reconciliation', [
            'invoice_id' => $apInvoice->id,
            'invoice_number' => $apInvoice->invoice_number,
            'purchase_order_id' => $apInvoice->purchase_order_id,
        ]);

        // Validate invoice has a purchase order
        if (!$apInvoice->purchase_order_id) {
            throw new \Exception('AP Invoice must be linked to a Purchase Order for reconciliation.');
        }

        $purchaseOrder = $apInvoice->purchaseOrder;

        if (!$purchaseOrder) {
            throw new \Exception("Purchase Order #{$apInvoice->purchase_order_id} not found.");
        }

        // Validate PO is approved (if approval is required)
        if ($purchaseOrder->approval_status === 'pending') {
            throw new \Exception("Purchase Order #{$purchaseOrder->order_number} is pending approval. Cannot reconcile.");
        }

        if ($purchaseOrder->approval_status === 'rejected') {
            throw new \Exception("Purchase Order #{$purchaseOrder->order_number} has been rejected. Cannot reconcile.");
        }

        // Perform reconciliation checks
        $discrepancies = [];
        $reconciliationStatus = 'matched';

        // Check 1: Total amount variance (with tolerance)
        $amountVariance = $this->validateAmountTolerance($apInvoice, $purchaseOrder, $discrepancies);

        // Check 2: Unit price variance (alert only, not blocking)
        $priceVariance = $this->validatePriceVariance($apInvoice, $purchaseOrder, $discrepancies);

        // Check 3: Validate invoice items exist in PO
        $this->validateItemsExist($apInvoice, $purchaseOrder, $discrepancies);

        // Determine final reconciliation status
        if (count($discrepancies) > 0) {
            // If amount variance exceeds tolerance, it's a critical discrepancy
            if ($amountVariance > config('purchase.reconciliation_tolerance_percent', 5)) {
                $reconciliationStatus = 'discrepancy';
            } else {
                // Minor discrepancies (like price variance warnings)
                $reconciliationStatus = 'matched'; // Still matched, but with notes
            }
        }

        // Update invoice reconciliation fields
        $updateData = [
            'reconciliation_status' => $reconciliationStatus,
            'reconciled_at' => now(),
            'reconciled_by' => $userId,
            'discrepancies' => count($discrepancies) > 0 ? $discrepancies : null,
        ];

        $apInvoice->update($updateData);

        Log::info('AP Invoice reconciliation completed', [
            'invoice_id' => $apInvoice->id,
            'invoice_number' => $apInvoice->invoice_number,
            'reconciliation_status' => $reconciliationStatus,
            'discrepancies_count' => count($discrepancies),
        ]);

        return [
            'status' => $reconciliationStatus,
            'discrepancies' => $discrepancies,
            'reconciled_at' => $apInvoice->reconciled_at,
            'reconciled_by' => $userId,
        ];
    }

    /**
     * Validate amount tolerance between invoice and PO
     *
     * @param APInvoice $apInvoice
     * @param PurchaseOrder $purchaseOrder
     * @param array $discrepancies
     * @return float Variance percentage
     */
    protected function validateAmountTolerance(APInvoice $apInvoice, PurchaseOrder $purchaseOrder, array &$discrepancies): float
    {
        $invoiceTotal = $apInvoice->total_amount;
        $poTotal = $purchaseOrder->total_amount;
        $tolerance = config('purchase.reconciliation_tolerance_percent', 5);

        $variance = abs($invoiceTotal - $poTotal);
        $variancePercent = $poTotal > 0 ? ($variance / $poTotal) * 100 : 0;

        if ($variancePercent > $tolerance) {
            $discrepancies[] = [
                'type' => 'amount_variance',
                'severity' => 'critical',
                'message' => sprintf(
                    'Invoice total ($%.2f) differs from PO total ($%.2f) by %.2f%% (tolerance: %d%%)',
                    $invoiceTotal,
                    $poTotal,
                    $variancePercent,
                    $tolerance
                ),
                'invoice_total' => $invoiceTotal,
                'po_total' => $poTotal,
                'variance_percent' => round($variancePercent, 2),
                'tolerance_percent' => $tolerance,
            ];
        }

        return $variancePercent;
    }

    /**
     * Validate unit price variance (warning only)
     *
     * @param APInvoice $apInvoice
     * @param PurchaseOrder $purchaseOrder
     * @param array $discrepancies
     * @return float Maximum variance percentage found
     */
    protected function validatePriceVariance(APInvoice $apInvoice, PurchaseOrder $purchaseOrder, array &$discrepancies): float
    {
        $warningThreshold = config('purchase.price_variance_warning_percent', 10);
        $maxVariance = 0;

        // Get PO items for comparison
        $poItems = $purchaseOrder->purchaseOrderItems;

        if ($poItems->isEmpty()) {
            return 0;
        }

        // Calculate average unit price from PO
        $avgPoUnitPrice = $poItems->avg('unit_price');

        // Calculate average unit price from Invoice (invoice total / total quantity)
        // Since we don't have line items, we use the total
        $invoiceUnitPrice = $apInvoice->total_amount / max($poItems->sum('quantity'), 1);

        if ($avgPoUnitPrice > 0) {
            $variance = abs($invoiceUnitPrice - $avgPoUnitPrice);
            $variancePercent = ($variance / $avgPoUnitPrice) * 100;
            $maxVariance = max($maxVariance, $variancePercent);

            if ($variancePercent > $warningThreshold) {
                $discrepancies[] = [
                    'type' => 'price_variance',
                    'severity' => 'warning',
                    'message' => sprintf(
                        'Average unit price variance of %.2f%% detected (warning threshold: %d%%)',
                        $variancePercent,
                        $warningThreshold
                    ),
                    'invoice_avg_price' => round($invoiceUnitPrice, 2),
                    'po_avg_price' => round($avgPoUnitPrice, 2),
                    'variance_percent' => round($variancePercent, 2),
                ];
            }
        }

        return $maxVariance;
    }

    /**
     * Validate that invoice references valid PO items
     *
     * @param APInvoice $apInvoice
     * @param PurchaseOrder $purchaseOrder
     * @param array $discrepancies
     */
    protected function validateItemsExist(APInvoice $apInvoice, PurchaseOrder $purchaseOrder, array &$discrepancies): void
    {
        $poItems = $purchaseOrder->purchaseOrderItems;

        if ($poItems->isEmpty()) {
            $discrepancies[] = [
                'type' => 'missing_po_items',
                'severity' => 'critical',
                'message' => 'Purchase Order has no line items to reconcile against.',
            ];
        }
    }

    /**
     * Approve a reconciled invoice
     *
     * @param APInvoice $apInvoice
     * @param int $userId
     * @param string|null $notes
     * @return bool
     */
    public function approveReconciliation(APInvoice $apInvoice, int $userId, ?string $notes = null): bool
    {
        if ($apInvoice->reconciliation_status === 'pending') {
            throw new \Exception('Invoice must be reconciled before approval.');
        }

        $apInvoice->update([
            'reconciliation_status' => 'approved',
            'reconciled_at' => now(),
            'reconciled_by' => $userId,
            'reconciliation_notes' => $notes,
        ]);

        Log::info('AP Invoice reconciliation approved', [
            'invoice_id' => $apInvoice->id,
            'invoice_number' => $apInvoice->invoice_number,
            'approved_by' => $userId,
        ]);

        return true;
    }

    /**
     * Check if an invoice can be reconciled
     *
     * @param APInvoice $apInvoice
     * @return bool
     */
    public function canReconcile(APInvoice $apInvoice): bool
    {
        if (!$apInvoice->purchase_order_id) {
            return false;
        }

        if (!$apInvoice->purchaseOrder) {
            return false;
        }

        $po = $apInvoice->purchaseOrder;

        if (in_array($po->approval_status, ['pending', 'rejected'])) {
            return false;
        }

        return true;
    }

    /**
     * Get reconciliation summary for reporting
     *
     * @return array
     */
    public function getReconciliationSummary(): array
    {
        return [
            'pending' => APInvoice::where('reconciliation_status', 'pending')->count(),
            'matched' => APInvoice::where('reconciliation_status', 'matched')->count(),
            'discrepancy' => APInvoice::where('reconciliation_status', 'discrepancy')->count(),
            'approved' => APInvoice::where('reconciliation_status', 'approved')->count(),
        ];
    }
}

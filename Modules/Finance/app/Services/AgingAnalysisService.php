<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * AgingAnalysisService
 *
 * Genera reportes de antigüedad de saldos (aging reports) para AR y AP
 *
 * Business Rules:
 * - Aging buckets: current, 1-30, 31-60, 61-90, 90+
 * - Basado en due_date vs as_of_date
 * - Solo incluye invoices no pagadas completamente
 * - Calcula balance pendiente (total - paid)
 * - Agrupa por contact para análisis por cliente/proveedor
 */
class AgingAnalysisService
{
    /**
     * Generate AR Aging Report (Accounts Receivable - Cuentas por Cobrar)
     *
     * @param string|null $asOfDate Fecha de corte (default: hoy)
     * @param int|null $contactId Filtrar por contact específico (opcional)
     * @return Collection
     */
    public function generateARAging(?string $asOfDate = null, ?int $contactId = null): Collection
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        $query = ARInvoice::with('contact')
            ->where('status', '!=', 'paid')
            ->where('invoice_date', '<=', $asOfDate)
            ->where('is_active', true);

        // Filtrar por contact si se especifica
        if ($contactId) {
            $query->where('contact_id', $contactId);
        }

        return $query->get()
            ->map(function ($invoice) use ($asOfDate) {
                $balance = $invoice->total_amount - $invoice->paid_amount;

                // Calcular días vencidos (positivo = vencido, negativo = por vencer)
                $daysOverdue = (int) Carbon::parse($invoice->due_date)
                    ->diffInDays(Carbon::parse($asOfDate), false);

                return [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'contact_id' => $invoice->contact_id,
                    'contact_name' => $invoice->contact?->name ?? 'N/A',
                    'invoice_date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'currency' => $invoice->currency,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'balance_amount' => $balance,
                    'days_overdue' => $daysOverdue,
                    'aging_bucket' => $this->getAgingBucket($daysOverdue),
                    'status' => $invoice->status,
                ];
            })
            ->sortBy([
                ['contact_name', 'asc'],
                ['days_overdue', 'desc'],
            ]);
    }

    /**
     * Generate AP Aging Report (Accounts Payable - Cuentas por Pagar)
     *
     * @param string|null $asOfDate Fecha de corte (default: hoy)
     * @param int|null $contactId Filtrar por contact específico (opcional)
     * @return Collection
     */
    public function generateAPAging(?string $asOfDate = null, ?int $contactId = null): Collection
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        $query = APInvoice::with('contact')
            ->where('status', '!=', 'paid')
            ->where('invoice_date', '<=', $asOfDate)
            ->where('is_active', true);

        // Filtrar por contact si se especifica
        if ($contactId) {
            $query->where('contact_id', $contactId);
        }

        return $query->get()
            ->map(function ($invoice) use ($asOfDate) {
                $balance = $invoice->total_amount - $invoice->paid_amount;

                // Calcular días vencidos
                $daysOverdue = (int) Carbon::parse($invoice->due_date)
                    ->diffInDays(Carbon::parse($asOfDate), false);

                return [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'contact_id' => $invoice->contact_id,
                    'contact_name' => $invoice->contact?->name ?? 'N/A',
                    'invoice_date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'currency' => $invoice->currency,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'balance_amount' => $balance,
                    'days_overdue' => $daysOverdue,
                    'aging_bucket' => $this->getAgingBucket($daysOverdue),
                    'status' => $invoice->status,
                ];
            })
            ->sortBy([
                ['contact_name', 'asc'],
                ['days_overdue', 'desc'],
            ]);
    }

    /**
     * Generate AR Aging Summary by Contact
     *
     * Agrupa el aging por contact para un resumen ejecutivo
     *
     * @param string|null $asOfDate
     * @return Collection
     */
    public function generateARAgingSummary(?string $asOfDate = null): Collection
    {
        $aging = $this->generateARAging($asOfDate);

        return $aging->groupBy('contact_id')
            ->map(function ($invoices, $contactId) {
                $totalBalance = $invoices->sum('balance_amount');

                return [
                    'contact_id' => $contactId,
                    'contact_name' => $invoices->first()['contact_name'],
                    'total_balance' => $totalBalance,
                    'invoice_count' => $invoices->count(),
                    'current' => $invoices->where('aging_bucket', 'current')->sum('balance_amount'),
                    '1-30' => $invoices->where('aging_bucket', '1-30')->sum('balance_amount'),
                    '31-60' => $invoices->where('aging_bucket', '31-60')->sum('balance_amount'),
                    '61-90' => $invoices->where('aging_bucket', '61-90')->sum('balance_amount'),
                    '90+' => $invoices->where('aging_bucket', '90+')->sum('balance_amount'),
                    'oldest_invoice_days' => $invoices->max('days_overdue'),
                ];
            })
            ->sortByDesc('total_balance')
            ->values();
    }

    /**
     * Generate AP Aging Summary by Contact
     *
     * @param string|null $asOfDate
     * @return Collection
     */
    public function generateAPAgingSummary(?string $asOfDate = null): Collection
    {
        $aging = $this->generateAPAging($asOfDate);

        return $aging->groupBy('contact_id')
            ->map(function ($invoices, $contactId) {
                $totalBalance = $invoices->sum('balance_amount');

                return [
                    'contact_id' => $contactId,
                    'contact_name' => $invoices->first()['contact_name'],
                    'total_balance' => $totalBalance,
                    'invoice_count' => $invoices->count(),
                    'current' => $invoices->where('aging_bucket', 'current')->sum('balance_amount'),
                    '1-30' => $invoices->where('aging_bucket', '1-30')->sum('balance_amount'),
                    '31-60' => $invoices->where('aging_bucket', '31-60')->sum('balance_amount'),
                    '61-90' => $invoices->where('aging_bucket', '61-90')->sum('balance_amount'),
                    '90+' => $invoices->where('aging_bucket', '90+')->sum('balance_amount'),
                    'oldest_invoice_days' => $invoices->max('days_overdue'),
                ];
            })
            ->sortByDesc('total_balance')
            ->values();
    }

    /**
     * Get total AR balance (all unpaid invoices)
     *
     * @return float
     */
    public function getTotalARBalance(): float
    {
        return ARInvoice::where('status', '!=', 'paid')
            ->where('is_active', true)
            ->get()
            ->sum(fn($invoice) => $invoice->total_amount - $invoice->paid_amount);
    }

    /**
     * Get total AP balance (all unpaid invoices)
     *
     * @return float
     */
    public function getTotalAPBalance(): float
    {
        return APInvoice::where('status', '!=', 'paid')
            ->where('is_active', true)
            ->get()
            ->sum(fn($invoice) => $invoice->total_amount - $invoice->paid_amount);
    }

    /**
     * Get AR balance for specific contact
     *
     * @param int $contactId
     * @return float
     */
    public function getContactARBalance(int $contactId): float
    {
        return ARInvoice::where('contact_id', $contactId)
            ->where('status', '!=', 'paid')
            ->where('is_active', true)
            ->get()
            ->sum(fn($invoice) => $invoice->total_amount - $invoice->paid_amount);
    }

    /**
     * Get AP balance for specific contact
     *
     * @param int $contactId
     * @return float
     */
    public function getContactAPBalance(int $contactId): float
    {
        return APInvoice::where('contact_id', $contactId)
            ->where('status', '!=', 'paid')
            ->where('is_active', true)
            ->get()
            ->sum(fn($invoice) => $invoice->total_amount - $invoice->paid_amount);
    }

    /**
     * Determine aging bucket based on days overdue
     *
     * @param int $daysOverdue Positivo = vencido, Negativo = por vencer
     * @return string
     */
    private function getAgingBucket(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 0 => 'current',
            $daysOverdue <= 30 => '1-30',
            $daysOverdue <= 60 => '31-60',
            $daysOverdue <= 90 => '61-90',
            default => '90+'
        };
    }
}

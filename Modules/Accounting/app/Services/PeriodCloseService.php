<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\BankTransaction;
use Modules\User\Models\User;

/**
 * AC-M001: Period Close Checklist Service
 *
 * Provides validation and closing functionality for fiscal periods.
 */
class PeriodCloseService
{
    /**
     * Get the close checklist for a fiscal period.
     *
     * @param FiscalPeriod $period
     * @return array
     */
    public function getCloseChecklist(FiscalPeriod $period): array
    {
        $checks = [
            $this->checkJournalEntriesBalanced($period),
            $this->checkAllEntriesPosted($period),
            $this->checkNoUnreconciledARInvoices($period),
            $this->checkNoUnreconciledAPInvoices($period),
            $this->checkBankReconciliationComplete($period),
            $this->checkNoPendingAdjustments($period),
        ];

        $allPassed = collect($checks)->every(fn($check) => $check['passed']);
        $warnings = collect($checks)->filter(fn($check) => !$check['passed'] && $check['severity'] === 'warning');
        $errors = collect($checks)->filter(fn($check) => !$check['passed'] && $check['severity'] === 'error');

        return [
            'period_id' => $period->id,
            'period_name' => $period->name,
            'status' => $period->status,
            'can_close' => $allPassed || ($errors->isEmpty() && $warnings->isNotEmpty()),
            'all_passed' => $allPassed,
            'total_checks' => count($checks),
            'passed_checks' => collect($checks)->filter(fn($check) => $check['passed'])->count(),
            'warnings_count' => $warnings->count(),
            'errors_count' => $errors->count(),
            'checks' => $checks,
        ];
    }

    /**
     * Check if all journal entries are balanced (debits = credits).
     */
    protected function checkJournalEntriesBalanced(FiscalPeriod $period): array
    {
        $unbalancedEntries = JournalEntry::where('fiscal_period_id', $period->id)
            ->whereRaw('ABS(total_debit - total_credit) > 0.01')
            ->count();

        return [
            'id' => 'journal_entries_balanced',
            'name' => 'Asientos contables balanceados',
            'description' => 'Todos los asientos deben tener débitos = créditos',
            'passed' => $unbalancedEntries === 0,
            'severity' => 'error',
            'details' => $unbalancedEntries > 0
                ? "Hay {$unbalancedEntries} asientos desbalanceados"
                : 'Todos los asientos están balanceados',
            'count' => $unbalancedEntries,
        ];
    }

    /**
     * Check if all journal entries are posted.
     */
    protected function checkAllEntriesPosted(FiscalPeriod $period): array
    {
        $unpostedEntries = JournalEntry::where('fiscal_period_id', $period->id)
            ->whereNotIn('status', ['posted', 'reversed'])
            ->count();

        return [
            'id' => 'all_entries_posted',
            'name' => 'Asientos contabilizados',
            'description' => 'Todos los asientos deben estar en estado "posted"',
            'passed' => $unpostedEntries === 0,
            'severity' => 'error',
            'details' => $unpostedEntries > 0
                ? "Hay {$unpostedEntries} asientos sin contabilizar"
                : 'Todos los asientos están contabilizados',
            'count' => $unpostedEntries,
        ];
    }

    /**
     * Check if all AR invoices for the period are reconciled.
     */
    protected function checkNoUnreconciledARInvoices(FiscalPeriod $period): array
    {
        $unreconciledCount = ARInvoice::whereBetween('invoice_date', [$period->start_date, $period->end_date])
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->count();

        return [
            'id' => 'ar_invoices_reconciled',
            'name' => 'Facturas por cobrar conciliadas',
            'description' => 'Todas las facturas por cobrar deben estar pagadas o canceladas',
            'passed' => $unreconciledCount === 0,
            'severity' => 'warning',
            'details' => $unreconciledCount > 0
                ? "Hay {$unreconciledCount} facturas por cobrar pendientes"
                : 'Todas las facturas por cobrar están conciliadas',
            'count' => $unreconciledCount,
        ];
    }

    /**
     * Check if all AP invoices for the period are reconciled.
     */
    protected function checkNoUnreconciledAPInvoices(FiscalPeriod $period): array
    {
        $unreconciledCount = APInvoice::whereBetween('invoice_date', [$period->start_date, $period->end_date])
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->count();

        return [
            'id' => 'ap_invoices_reconciled',
            'name' => 'Facturas por pagar conciliadas',
            'description' => 'Todas las facturas por pagar deben estar pagadas o canceladas',
            'passed' => $unreconciledCount === 0,
            'severity' => 'warning',
            'details' => $unreconciledCount > 0
                ? "Hay {$unreconciledCount} facturas por pagar pendientes"
                : 'Todas las facturas por pagar están conciliadas',
            'count' => $unreconciledCount,
        ];
    }

    /**
     * Check if bank reconciliation is complete.
     */
    protected function checkBankReconciliationComplete(FiscalPeriod $period): array
    {
        $unreconciledCount = BankTransaction::whereBetween('transaction_date', [$period->start_date, $period->end_date])
            ->whereNull('reconciled_at')
            ->count();

        return [
            'id' => 'bank_reconciliation_complete',
            'name' => 'Conciliación bancaria completa',
            'description' => 'Todas las transacciones bancarias deben estar conciliadas',
            'passed' => $unreconciledCount === 0,
            'severity' => 'warning',
            'details' => $unreconciledCount > 0
                ? "Hay {$unreconciledCount} transacciones bancarias sin conciliar"
                : 'Todas las transacciones bancarias están conciliadas',
            'count' => $unreconciledCount,
        ];
    }

    /**
     * Check if there are no pending adjustments.
     */
    protected function checkNoPendingAdjustments(FiscalPeriod $period): array
    {
        $pendingAdjustments = JournalEntry::where('fiscal_period_id', $period->id)
            ->where('status', 'draft')
            ->where('description', 'like', '%ajuste%')
            ->count();

        return [
            'id' => 'no_pending_adjustments',
            'name' => 'Sin ajustes pendientes',
            'description' => 'No debe haber asientos de ajuste en borrador',
            'passed' => $pendingAdjustments === 0,
            'severity' => 'warning',
            'details' => $pendingAdjustments > 0
                ? "Hay {$pendingAdjustments} ajustes pendientes de aplicar"
                : 'No hay ajustes pendientes',
            'count' => $pendingAdjustments,
        ];
    }

    /**
     * Close a fiscal period.
     *
     * @param FiscalPeriod $period
     * @param User $user
     * @param bool $force Force close even with warnings
     * @return array
     */
    public function closePeriod(FiscalPeriod $period, User $user, bool $force = false): array
    {
        // Check if already closed
        if ($period->status === 'closed') {
            return [
                'success' => false,
                'message' => 'El período ya está cerrado',
                'period' => $period,
            ];
        }

        // Get checklist
        $checklist = $this->getCloseChecklist($period);

        // Check if can close
        if (!$checklist['can_close'] && !$force) {
            return [
                'success' => false,
                'message' => 'No se puede cerrar el período. Hay errores pendientes.',
                'checklist' => $checklist,
            ];
        }

        // If there are errors and force is not set
        if ($checklist['errors_count'] > 0 && !$force) {
            return [
                'success' => false,
                'message' => 'Hay errores críticos que impiden el cierre',
                'checklist' => $checklist,
            ];
        }

        return DB::transaction(function () use ($period, $user, $checklist) {
            // Update period status
            $period->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by_id' => $user->id,
                'metadata' => array_merge($period->metadata ?? [], [
                    'close_checklist' => $checklist,
                    'closed_with_warnings' => $checklist['warnings_count'] > 0,
                ]),
            ]);

            return [
                'success' => true,
                'message' => 'Período cerrado exitosamente',
                'period' => $period->fresh(),
                'checklist' => $checklist,
            ];
        });
    }

    /**
     * Reopen a closed fiscal period.
     *
     * @param FiscalPeriod $period
     * @param User $user
     * @param string|null $reason
     * @return array
     */
    public function reopenPeriod(FiscalPeriod $period, User $user, ?string $reason = null): array
    {
        // Check if period is closed
        if ($period->status !== 'closed') {
            return [
                'success' => false,
                'message' => 'El período no está cerrado',
                'period' => $period,
            ];
        }

        return DB::transaction(function () use ($period, $user, $reason) {
            $previousMetadata = $period->metadata ?? [];

            // Record reopen history
            $reopenHistory = $previousMetadata['reopen_history'] ?? [];
            $reopenHistory[] = [
                'reopened_at' => now()->toIso8601String(),
                'reopened_by_id' => $user->id,
                'reopened_by_name' => $user->name,
                'reason' => $reason,
                'was_closed_at' => $period->closed_at?->toIso8601String(),
                'was_closed_by_id' => $period->closed_by_id,
            ];

            // Update period status
            $period->update([
                'status' => 'open',
                'closed_at' => null,
                'closed_by_id' => null,
                'metadata' => array_merge($previousMetadata, [
                    'reopen_history' => $reopenHistory,
                    'last_reopened_at' => now()->toIso8601String(),
                    'last_reopened_by_id' => $user->id,
                    'last_reopen_reason' => $reason,
                ]),
            ]);

            return [
                'success' => true,
                'message' => 'Período reabierto exitosamente',
                'period' => $period->fresh(),
            ];
        });
    }

    /**
     * Get period summary with financial totals.
     *
     * @param FiscalPeriod $period
     * @return array
     */
    public function getPeriodSummary(FiscalPeriod $period): array
    {
        $entries = JournalEntry::where('fiscal_period_id', $period->id)
            ->where('status', 'posted')
            ->get();

        $totalDebits = $entries->sum('total_debit');
        $totalCredits = $entries->sum('total_credit');

        $arInvoices = ARInvoice::whereBetween('invoice_date', [$period->start_date, $period->end_date]);
        $apInvoices = APInvoice::whereBetween('invoice_date', [$period->start_date, $period->end_date]);

        return [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'year' => $period->year,
                'month' => $period->month,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
                'status' => $period->status,
            ],
            'journal_entries' => [
                'total_count' => $entries->count(),
                'total_debits' => round($totalDebits, 2),
                'total_credits' => round($totalCredits, 2),
                'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
            ],
            'accounts_receivable' => [
                'total_invoices' => $arInvoices->count(),
                'total_amount' => round($arInvoices->sum('total_amount'), 2),
                'paid_count' => (clone $arInvoices)->where('status', 'paid')->count(),
                'pending_count' => (clone $arInvoices)->whereNotIn('status', ['paid', 'cancelled'])->count(),
            ],
            'accounts_payable' => [
                'total_invoices' => $apInvoices->count(),
                'total_amount' => round($apInvoices->sum('total_amount'), 2),
                'paid_count' => (clone $apInvoices)->where('status', 'paid')->count(),
                'pending_count' => (clone $apInvoices)->whereNotIn('status', ['paid', 'cancelled'])->count(),
            ],
        ];
    }
}

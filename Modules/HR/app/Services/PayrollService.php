<?php

namespace Modules\HR\Services;

use Illuminate\Support\Facades\DB;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\PayrollItem;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Models\Account;
use Carbon\Carbon;

class PayrollService
{
    /**
     * Process a payroll period - calculate totals and update period status.
     *
     * @param PayrollPeriod $period
     * @return PayrollPeriod
     */
    public function processPeriod(PayrollPeriod $period): PayrollPeriod
    {
        DB::beginTransaction();

        try {
            // Calculate totals from all payroll items in this period
            $items = PayrollItem::where('payroll_period_id', $period->id)->get();

            $totalGross = 0;
            $totalDeductions = 0;

            foreach ($items as $item) {
                // Calculate gross for each item (basic_salary + overtime_pay + bonuses)
                $itemGross = $item->basic_salary + $item->overtime_pay + $item->bonuses;
                $totalGross += $itemGross;
                $totalDeductions += $item->deductions;
            }

            // Update period totals
            $period->total_gross = $totalGross;
            $period->total_deductions = $totalDeductions;
            $period->total_net = $totalGross - $totalDeductions; // Calculated in model boot
            $period->status = 'processing';
            $period->save();

            DB::commit();

            return $period->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark a payroll period as paid and post to General Ledger.
     *
     * @param PayrollPeriod $period
     * @param int $userId User who approved the payment
     * @return array ['period' => PayrollPeriod, 'journal_entry' => JournalEntry]
     * @throws \Exception
     */
    public function markAsPaid(PayrollPeriod $period, int $userId): array
    {
        if ($period->status === 'paid') {
            throw new \Exception("El período de nómina ya está marcado como pagado.");
        }

        if ($period->status === 'draft') {
            throw new \Exception("El período de nómina debe estar en proceso antes de marcarlo como pagado.");
        }

        DB::beginTransaction();

        try {
            // Mark period as paid
            $period->status = 'paid';
            $period->save();

            // Mark all items as paid
            PayrollItem::where('payroll_period_id', $period->id)
                ->where('status', '!=', 'paid')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

            // Post to General Ledger
            $journalEntry = $this->postToGeneralLedger($period, $userId);

            DB::commit();

            return [
                'period' => $period->fresh(),
                'journal_entry' => $journalEntry,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Post payroll to General Ledger.
     *
     * Creates a journal entry with:
     * - Debit: Payroll Expense (Gasto de Nómina)
     * - Credit: Bank Account (for net payment)
     * - Credit: Various liability accounts (for deductions)
     *
     * @param PayrollPeriod $period
     * @param int $userId
     * @return JournalEntry
     */
    protected function postToGeneralLedger(PayrollPeriod $period, int $userId): JournalEntry
    {
        // Find accounts (using Spanish names from Finance/Accounting Phase 1)
        $payrollExpenseAccount = Account::where('code', 'LIKE', '6%')
            ->where('name', 'LIKE', '%nómina%')
            ->first();

        $bankAccount = Account::where('code', 'LIKE', '1%')
            ->where('name', 'LIKE', '%banco%')
            ->first();

        $liabilitiesAccount = Account::where('code', 'LIKE', '2%')
            ->where('name', 'LIKE', '%pasivo%')
            ->first();

        if (!$payrollExpenseAccount) {
            throw new \Exception("No se encontró cuenta de gasto de nómina (código 6xxx).");
        }

        if (!$bankAccount) {
            throw new \Exception("No se encontró cuenta bancaria (código 1xxx).");
        }

        // Create journal entry
        $journalEntry = JournalEntry::create([
            'fiscal_period_id' => $this->getCurrentFiscalPeriodId(),
            'entry_date' => $period->payment_date ?? now(),
            'reference' => 'PAYROLL-' . $period->id,
            'description' => "Pago de nómina: {$period->name}",
            'status' => 'draft',
            'created_by' => $userId,
        ]);

        // Debit: Payroll Expense (total gross)
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $payrollExpenseAccount->id,
            'debit' => $period->total_gross,
            'credit' => 0,
            'description' => "Gasto de nómina - {$period->name}",
        ]);

        // Credit: Bank Account (net payment)
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $bankAccount->id,
            'debit' => 0,
            'credit' => $period->total_net,
            'description' => "Pago neto de nómina - {$period->name}",
        ]);

        // Credit: Liabilities (deductions)
        if ($period->total_deductions > 0 && $liabilitiesAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $liabilitiesAccount->id,
                'debit' => 0,
                'credit' => $period->total_deductions,
                'description' => "Deducciones de nómina - {$period->name}",
            ]);
        }

        // Post journal entry automatically
        $journalEntry->status = 'posted';
        $journalEntry->posted_at = now();
        $journalEntry->posted_by = $userId;
        $journalEntry->save();

        return $journalEntry;
    }

    /**
     * Get current fiscal period ID.
     * Uses the first open fiscal period found.
     *
     * @return int
     * @throws \Exception
     */
    protected function getCurrentFiscalPeriodId(): int
    {
        $fiscalPeriod = \Modules\Accounting\Models\FiscalPeriod::where('status', 'open')
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$fiscalPeriod) {
            throw new \Exception("No hay un período fiscal abierto para registrar la nómina.");
        }

        return $fiscalPeriod->id;
    }

    /**
     * Calculate payroll item totals.
     * This is useful when creating/updating individual items.
     *
     * @param array $data
     * @return array Calculated values
     */
    public function calculateItemTotals(array $data): array
    {
        $basicSalary = $data['basic_salary'] ?? 0;
        $overtimePay = $data['overtime_pay'] ?? 0;
        $bonuses = $data['bonuses'] ?? 0;
        $deductions = $data['deductions'] ?? 0;

        $grossPay = $basicSalary + $overtimePay + $bonuses;
        $netPay = $grossPay - $deductions;

        return [
            'gross_pay' => $grossPay,
            'net_pay' => $netPay,
        ];
    }

    /**
     * Close a payroll period.
     * Once closed, no modifications are allowed.
     *
     * @param PayrollPeriod $period
     * @return PayrollPeriod
     * @throws \Exception
     */
    public function closePeriod(PayrollPeriod $period): PayrollPeriod
    {
        if ($period->status !== 'paid') {
            throw new \Exception("Solo se pueden cerrar períodos que han sido pagados.");
        }

        $period->status = 'closed';
        $period->save();

        return $period;
    }

    /**
     * Reopen a closed period for corrections.
     * Should be used with caution.
     *
     * @param PayrollPeriod $period
     * @return PayrollPeriod
     * @throws \Exception
     */
    public function reopenPeriod(PayrollPeriod $period): PayrollPeriod
    {
        if ($period->status !== 'closed') {
            throw new \Exception("Solo se pueden reabrir períodos cerrados.");
        }

        $period->status = 'processing';
        $period->save();

        return $period;
    }
}

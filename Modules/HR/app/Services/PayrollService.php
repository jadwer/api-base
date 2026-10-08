<?php

namespace Modules\HR\Services;

use Illuminate\Support\Facades\DB;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\PayrollItem;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\AccountingService;

class PayrollService
{
    public function __construct(private AccountingService $accountingService)
    {
    }

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
        // Find accounts (using Spanish names from Finance/Accounting Phase 1).
        // Solo cuentas afectables y activas: las de titulo (PASIVO, GASTOS) no aceptan asientos.
        $payrollExpenseAccount = Account::where('code', 'LIKE', '6%')
            ->where('name', 'LIKE', '%nómina%')
            ->where('is_postable', true)
            ->where('status', 'active')
            ->first();

        $bankAccount = Account::where('code', 'LIKE', '1%')
            ->where('name', 'LIKE', '%banco%')
            ->where('is_postable', true)
            ->where('status', 'active')
            ->first();

        $liabilitiesAccount = Account::where('code', 'LIKE', '2%')
            ->where('name', 'LIKE', '%pasivo%')
            ->where('is_postable', true)
            ->where('status', 'active')
            ->first();

        if (!$payrollExpenseAccount) {
            throw new \Exception("No se encontró cuenta de gasto de nómina (código 6xxx).");
        }

        if (!$bankAccount) {
            throw new \Exception("No se encontró cuenta bancaria (código 1xxx).");
        }

        $deductions = (float) $period->total_deductions;
        if ($deductions > 0 && !$liabilitiesAccount) {
            throw new \Exception("No se encontró cuenta de pasivo para las deducciones de nómina (código 2xxx).");
        }

        $lines = [
            [
                'account_id' => $payrollExpenseAccount->id,
                'debit_amount' => $period->total_gross,
                'credit_amount' => 0,
                'description' => "Gasto de nómina - {$period->name}",
            ],
            [
                'account_id' => $bankAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $period->total_net,
                'description' => "Pago neto de nómina - {$period->name}",
            ],
        ];

        if ($deductions > 0) {
            $lines[] = [
                'account_id' => $liabilitiesAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $deductions,
                'description' => "Deducciones de nómina - {$period->name}",
            ];
        }

        // Mismo camino que AR/AP/inventario: diario GL, periodo fiscal abierto
        // segun la fecha de pago, validacion de cuadre y folio del diario.
        $journalEntry = $this->accountingService->createJournalEntry(
            journalCode: 'GL',
            entryDate: ($period->payment_date ?? now())->toDateString(),
            description: "Pago de nómina: {$period->name}",
            reference: 'PAYROLL-' . $period->id,
            lines: $lines,
        );

        $journalEntry->update([
            'posted_by_id' => $userId,
            'source_type' => PayrollPeriod::class,
            'source_id' => $period->id,
        ]);

        return $journalEntry;
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

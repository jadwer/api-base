<?php

namespace Modules\Reports\Services\FinancialStatements;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;

class BalanceSheetService
{
    /**
     * Generate Balance Sheet report
     *
     * @param Carbon $asOfDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $asOfDate, string $currency = 'MXN'): array
    {
        // Get accounts grouped by type
        $assets = $this->getAccountsByType('asset', $asOfDate, $currency);
        $liabilities = $this->getAccountsByType('liability', $asOfDate, $currency);
        $equity = $this->getAccountsByType('equity', $asOfDate, $currency);

        // Cuentas complementarias (depreciacion acumulada, estimaciones): de
        // naturaleza acreedora, restan al activo.
        $contra = $this->getAccountsByType('contra', $asOfDate, $currency)
            ->map(fn ($a) => array_merge($a, ['account_type' => 'asset', 'balance' => -$a['balance']]));
        $assets = $assets->concat($contra)->values();

        // Sin polizas de cierre, el resultado del ejercicio (ingresos menos
        // gastos a la fecha) forma parte del capital; sin el, el balance no
        // cuadra (24-10).
        $netIncome = $this->calculateTotal($this->getAccountsByType('revenue', $asOfDate, $currency))
            - $this->calculateTotal($this->getAccountsByType('expense', $asOfDate, $currency));
        if (abs($netIncome) > 0.01) {
            $equity = $equity->push([
                'code' => '3',
                'name' => 'Resultado del ejercicio',
                'account_type' => 'equity',
                'balance' => $netIncome,
            ])->values();
        }

        // Calculate totals
        $totalAssets = $this->calculateTotal($assets);
        $totalLiabilities = $this->calculateTotal($liabilities);
        $totalEquity = $this->calculateTotal($equity);

        // Balance check: Assets = Liabilities + Equity
        $balanced = abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01;

        return [
            'asOfDate' => $asOfDate->format('Y-m-d'),
            'currency' => $currency,
            'balanced' => $balanced,
            'assets' => $this->formatAccountsHierarchy($assets),
            'totalAssets' => round($totalAssets, 2),
            'liabilities' => $this->formatAccountsHierarchy($liabilities),
            'totalLiabilities' => round($totalLiabilities, 2),
            'equity' => $this->formatAccountsHierarchy($equity),
            'totalEquity' => round($totalEquity, 2),
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Get accounts by type with their balances
     *
     * @param string $type
     * @param Carbon $asOfDate
     * @param string $currency
     * @return \Illuminate\Support\Collection
     */
    protected function getAccountsByType(string $type, Carbon $asOfDate, string $currency)
    {
        return Account::where('account_type', $type)
            ->where('status', 'active')
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($asOfDate, $currency) {
                $balance = $this->getAccountBalance($account, $asOfDate, $currency);

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type,
                    'balance' => $balance,
                ];
            })
            ->filter(function ($account) {
                // Only include accounts with non-zero balance
                return abs($account['balance']) > 0.01;
            });
    }

    /**
     * Get account balance as of date
     *
     * @param Account $account
     * @param Carbon $asOfDate
     * @param string $currency
     * @return float
     */
    protected function getAccountBalance(Account $account, Carbon $asOfDate, string $currency): float
    {
        // Calculate from JournalLines
        $debits = JournalLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('accounting_date', '<=', $asOfDate)
                    ->where('status', 'posted');
            })
            ->sum('debit');

        $credits = JournalLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('accounting_date', '<=', $asOfDate)
                    ->where('status', 'posted');
            })
            ->sum('credit');

        // Balance depends on account type
        if (in_array($account->account_type, ['asset', 'expense'])) {
            return (float) ($debits - $credits);
        } else {
            return (float) ($credits - $debits);
        }
    }

    /**
     * Calculate total from accounts collection
     *
     * @param \Illuminate\Support\Collection $accounts
     * @return float
     */
    protected function calculateTotal($accounts): float
    {
        return $accounts->sum('balance');
    }

    /**
     * Format accounts hierarchy for JSON response
     *
     * @param \Illuminate\Support\Collection $accounts
     * @return array
     */
    protected function formatAccountsHierarchy($accounts): array
    {
        // Group by account code prefix (first digit for main categories)
        $grouped = $accounts->groupBy(function ($account) {
            return substr($account['code'], 0, 1);
        });

        $hierarchy = [];
        foreach ($grouped as $prefix => $group) {
            $categoryName = $this->getCategoryName($prefix, $group->first()['account_type']);

            $hierarchy[] = [
                'category' => $categoryName,
                'accounts' => $group->values()->toArray(),
                'subtotal' => round($group->sum('balance'), 2),
            ];
        }

        return $hierarchy;
    }

    /**
     * Get category name based on prefix and type
     *
     * @param string $prefix
     * @param string $type
     * @return string
     */
    protected function getCategoryName(string $prefix, string $type): string
    {
        // Catalogo de cuentas: 1 activo, 2 pasivo, 3 capital.
        $categories = [
            'asset' => ['1' => 'Activo'],
            'liability' => ['2' => 'Pasivo'],
            'equity' => ['3' => 'Capital contable'],
        ];
        $fallback = ['asset' => 'Activo', 'liability' => 'Pasivo', 'equity' => 'Capital contable'];

        return $categories[$type][$prefix] ?? $fallback[$type] ?? $type;
    }
}

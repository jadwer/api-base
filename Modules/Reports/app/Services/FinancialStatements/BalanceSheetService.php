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
        $categories = [
            'asset' => [
                '1' => 'Activo Circulante',
                '2' => 'Activo Fijo',
                '3' => 'Activo Diferido',
            ],
            'liability' => [
                '4' => 'Pasivo Circulante',
                '5' => 'Pasivo a Largo Plazo',
            ],
            'equity' => [
                '6' => 'Capital Contable',
            ],
        ];

        return $categories[$type][$prefix] ?? ucfirst($type);
    }
}

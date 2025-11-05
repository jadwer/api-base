<?php

namespace Modules\Reports\Services\FinancialStatements;

use Carbon\Carbon;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;

class IncomeStatementService
{
    /**
     * Generate Income Statement report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $startDate, Carbon $endDate, string $currency = 'MXN'): array
    {
        // Get revenue and expense accounts
        $revenues = $this->getAccountsByType('revenue', $startDate, $endDate, $currency);
        $expenses = $this->getAccountsByType('expense', $startDate, $endDate, $currency);

        // Calculate totals
        $totalRevenues = $this->calculateTotal($revenues);
        $totalExpenses = $this->calculateTotal($expenses);
        $netIncome = $totalRevenues - $totalExpenses;

        return [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'currency' => $currency,
            'revenues' => $this->formatAccountsHierarchy($revenues, 'revenue'),
            'totalRevenues' => round($totalRevenues, 2),
            'expenses' => $this->formatAccountsHierarchy($expenses, 'expense'),
            'totalExpenses' => round($totalExpenses, 2),
            'netIncome' => round($netIncome, 2),
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Get accounts by type with their period activity
     *
     * @param string $type
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $currency
     * @return \Illuminate\Support\Collection
     */
    protected function getAccountsByType(string $type, Carbon $startDate, Carbon $endDate, string $currency)
    {
        return Account::where('account_type', $type)
            ->where('active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($startDate, $endDate, $currency) {
                $activity = $this->getAccountActivity($account, $startDate, $endDate, $currency);

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->type,
                    'amount' => $activity,
                ];
            })
            ->filter(function ($account) {
                // Only include accounts with activity
                return abs($account['amount']) > 0.01;
            });
    }

    /**
     * Get account activity for period
     *
     * @param Account $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $currency
     * @return float
     */
    protected function getAccountActivity(Account $account, Carbon $startDate, Carbon $endDate, string $currency): float
    {
        $debits = JournalLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('status', 'posted');
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('status', 'posted');
            })
            ->sum('credit_amount');

        // Revenue accounts: credits increase, debits decrease
        if ($account->type === 'revenue') {
            return (float) ($credits - $debits);
        }

        // Expense accounts: debits increase, credits decrease
        if ($account->type === 'expense') {
            return (float) ($debits - $credits);
        }

        return 0.0;
    }

    /**
     * Calculate total from accounts collection
     *
     * @param \Illuminate\Support\Collection $accounts
     * @return float
     */
    protected function calculateTotal($accounts): float
    {
        return $accounts->sum('amount');
    }

    /**
     * Format accounts hierarchy for JSON response
     *
     * @param \Illuminate\Support\Collection $accounts
     * @param string $type
     * @return array
     */
    protected function formatAccountsHierarchy($accounts, string $type): array
    {
        // Group by account code prefix
        $grouped = $accounts->groupBy(function ($account) {
            return substr($account['code'], 0, 1);
        });

        $hierarchy = [];
        foreach ($grouped as $prefix => $group) {
            $categoryName = $this->getCategoryName($prefix, $type);

            $hierarchy[] = [
                'category' => $categoryName,
                'accounts' => $group->values()->toArray(),
                'subtotal' => round($group->sum('amount'), 2),
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
            'revenue' => [
                '7' => 'Ingresos por Ventas',
                '8' => 'Otros Ingresos',
            ],
            'expense' => [
                '9' => 'Costos de Ventas',
                '0' => 'Gastos Operativos',
            ],
        ];

        return $categories[$type][$prefix] ?? ucfirst($type);
    }
}

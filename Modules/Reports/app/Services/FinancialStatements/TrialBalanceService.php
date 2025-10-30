<?php

namespace Modules\Reports\Services\FinancialStatements;

use Carbon\Carbon;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;

class TrialBalanceService
{
    /**
     * Generate Trial Balance report
     *
     * @param Carbon $asOfDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $asOfDate, string $currency = 'MXN'): array
    {
        $accounts = Account::where('active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($asOfDate) {
                $balances = $this->getAccountBalance($account, $asOfDate);

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => $balances['debit'],
                    'credit' => $balances['credit'],
                ];
            })
            ->filter(function ($account) {
                // Only include accounts with activity
                return $account['debit'] > 0.01 || $account['credit'] > 0.01;
            });

        $totalDebits = $accounts->sum('debit');
        $totalCredits = $accounts->sum('credit');
        $balanced = abs($totalDebits - $totalCredits) < 0.01;

        // Group by type
        $summaryByType = $accounts->groupBy('type')->map(function ($group, $type) {
            return [
                'type' => $type,
                'totalDebit' => round($group->sum('debit'), 2),
                'totalCredit' => round($group->sum('credit'), 2),
                'count' => $group->count(),
            ];
        })->values();

        return [
            'asOfDate' => $asOfDate->format('Y-m-d'),
            'currency' => $currency,
            'accounts' => $accounts->values()->toArray(),
            'totals' => [
                'debit' => round($totalDebits, 2),
                'credit' => round($totalCredits, 2),
            ],
            'balanced' => $balanced,
            'summaryByType' => $summaryByType->toArray(),
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Get account debit and credit balances
     *
     * @param Account $account
     * @param Carbon $asOfDate
     * @return array
     */
    protected function getAccountBalance(Account $account, Carbon $asOfDate): array
    {
        $debits = JournalLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('entry_date', '<=', $asOfDate)
                    ->where('status', 'posted');
            })
            ->sum('debit_amount');

        $credits = JournalLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('entry_date', '<=', $asOfDate)
                    ->where('status', 'posted');
            })
            ->sum('credit_amount');

        return [
            'debit' => round((float) $debits, 2),
            'credit' => round((float) $credits, 2),
        ];
    }
}

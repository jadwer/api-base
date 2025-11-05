<?php

namespace Modules\Reports\Services\FinancialStatements;

use Carbon\Carbon;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;

class CashFlowService
{
    /**
     * Generate Cash Flow Statement report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $startDate, Carbon $endDate, string $currency = 'MXN'): array
    {
        // Get cash and bank accounts
        $cashAccounts = Account::where('account_type', 'asset')
            ->where('code', 'LIKE', '1%') // Assuming cash accounts start with 1
            ->where('active', true)
            ->pluck('id')
            ->toArray();

        // Calculate cash flows
        $operatingCF = $this->getOperatingCashFlow($cashAccounts, $startDate, $endDate);
        $investingCF = $this->getInvestingCashFlow($cashAccounts, $startDate, $endDate);
        $financingCF = $this->getFinancingCashFlow($cashAccounts, $startDate, $endDate);

        $netCashFlow = $operatingCF + $investingCF + $financingCF;

        // Get beginning and ending cash balances
        $beginningCash = $this->getCashBalance($cashAccounts, $startDate);
        $endingCash = $beginningCash + $netCashFlow;

        return [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'currency' => $currency,
            'beginningCash' => round($beginningCash, 2),
            'operatingActivities' => round($operatingCF, 2),
            'investingActivities' => round($investingCF, 2),
            'financingActivities' => round($financingCF, 2),
            'netCashFlow' => round($netCashFlow, 2),
            'endingCash' => round($endingCash, 2),
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    protected function getOperatingCashFlow(array $cashAccounts, Carbon $startDate, Carbon $endDate): float
    {
        // Simplified: Sum of debits - credits to cash accounts from operating activities
        $debits = JournalLine::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('status', 'posted')
                    ->where('description', 'NOT LIKE', '%investment%')
                    ->where('description', 'NOT LIKE', '%loan%');
            })
            ->sum('debit_amount');

        $credits = JournalLine::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('status', 'posted')
                    ->where('description', 'NOT LIKE', '%investment%')
                    ->where('description', 'NOT LIKE', '%loan%');
            })
            ->sum('credit_amount');

        return (float) ($debits - $credits);
    }

    protected function getInvestingCashFlow(array $cashAccounts, Carbon $startDate, Carbon $endDate): float
    {
        // Cash flow from investments and asset purchases
        return 0.0; // Simplified for now
    }

    protected function getFinancingCashFlow(array $cashAccounts, Carbon $startDate, Carbon $endDate): float
    {
        // Cash flow from loans and equity
        return 0.0; // Simplified for now
    }

    protected function getCashBalance(array $cashAccounts, Carbon $asOfDate): float
    {
        $debits = JournalLine::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('entry_date', '<', $asOfDate)
                    ->where('status', 'posted');
            })
            ->sum('debit_amount');

        $credits = JournalLine::whereIn('account_id', $cashAccounts)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('entry_date', '<', $asOfDate)
                    ->where('status', 'posted');
            })
            ->sum('credit_amount');

        return (float) ($debits - $credits);
    }
}

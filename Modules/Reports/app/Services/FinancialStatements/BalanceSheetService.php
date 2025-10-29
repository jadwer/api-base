<?php

namespace Modules\Reports\Services\FinancialStatements;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;

class BalanceSheetService
{
    /**
     * Generate Balance Sheet (Estado de Situación Financiera)
     *
     * @param Carbon $asOfDate
     * @return array
     */
    public function generate(Carbon $asOfDate): array
    {
        $assets = $this->calculateAssets($asOfDate);
        $liabilities = $this->calculateLiabilities($asOfDate);
        $equity = $this->calculateEquity($asOfDate);

        return [
            'as_of_date' => $asOfDate->toDateString(),
            'currency' => config('app.currency', 'MXN'),
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'balanced' => abs($assets['total_assets'] - ($liabilities['total_liabilities'] + $equity['total_equity'])) < 0.01,
        ];
    }

    /**
     * Calculate Assets section
     *
     * @param Carbon $asOfDate
     * @return array
     */
    private function calculateAssets(Carbon $asOfDate): array
    {
        // Get all asset accounts
        $assetAccounts = Account::where('account_type', 'asset')
            ->where('is_active', true)
            ->get();

        $currentAssets = [];
        $nonCurrentAssets = [];

        foreach ($assetAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, $asOfDate);

            if ($balance != 0) {
                $accountData = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];

                // Classify as current or non-current based on code or name
                if ($this->isCurrentAsset($account)) {
                    $currentAssets[] = $accountData;
                } else {
                    $nonCurrentAssets[] = $accountData;
                }
            }
        }

        $currentAssetsTotal = array_sum(array_column($currentAssets, 'balance'));
        $nonCurrentAssetsTotal = array_sum(array_column($nonCurrentAssets, 'balance'));

        return [
            'current_assets' => [
                'accounts' => $currentAssets,
                'total' => $currentAssetsTotal,
            ],
            'non_current_assets' => [
                'accounts' => $nonCurrentAssets,
                'total' => $nonCurrentAssetsTotal,
            ],
            'total_assets' => $currentAssetsTotal + $nonCurrentAssetsTotal,
        ];
    }

    /**
     * Calculate Liabilities section
     *
     * @param Carbon $asOfDate
     * @return array
     */
    private function calculateLiabilities(Carbon $asOfDate): array
    {
        // Get all liability accounts
        $liabilityAccounts = Account::where('account_type', 'liability')
            ->where('is_active', true)
            ->get();

        $currentLiabilities = [];
        $nonCurrentLiabilities = [];

        foreach ($liabilityAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, $asOfDate);

            if ($balance != 0) {
                $accountData = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];

                // Classify as current or non-current
                if ($this->isCurrentLiability($account)) {
                    $currentLiabilities[] = $accountData;
                } else {
                    $nonCurrentLiabilities[] = $accountData;
                }
            }
        }

        $currentLiabilitiesTotal = array_sum(array_column($currentLiabilities, 'balance'));
        $nonCurrentLiabilitiesTotal = array_sum(array_column($nonCurrentLiabilities, 'balance'));

        return [
            'current_liabilities' => [
                'accounts' => $currentLiabilities,
                'total' => $currentLiabilitiesTotal,
            ],
            'non_current_liabilities' => [
                'accounts' => $nonCurrentLiabilities,
                'total' => $nonCurrentLiabilitiesTotal,
            ],
            'total_liabilities' => $currentLiabilitiesTotal + $nonCurrentLiabilitiesTotal,
        ];
    }

    /**
     * Calculate Equity section
     *
     * @param Carbon $asOfDate
     * @return array
     */
    private function calculateEquity(Carbon $asOfDate): array
    {
        // Get all equity accounts
        $equityAccounts = Account::where('account_type', 'equity')
            ->where('is_active', true)
            ->get();

        $equityItems = [];

        foreach ($equityAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, $asOfDate);

            if ($balance != 0) {
                $equityItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                ];
            }
        }

        // Calculate retained earnings from income statement
        $retainedEarnings = $this->calculateRetainedEarnings($asOfDate);

        // Add retained earnings if not already in equity accounts
        $hasRetainedEarnings = collect($equityItems)->contains('code', '3020');
        if (!$hasRetainedEarnings && $retainedEarnings != 0) {
            $equityItems[] = [
                'code' => '3020',
                'name' => 'Retained Earnings',
                'balance' => $retainedEarnings,
            ];
        }

        $totalEquity = array_sum(array_column($equityItems, 'balance'));

        return [
            'accounts' => $equityItems,
            'total_equity' => $totalEquity,
        ];
    }

    /**
     * Get account balance as of specific date
     *
     * @param int $accountId
     * @param Carbon $asOfDate
     * @return float
     */
    private function getAccountBalance(int $accountId, Carbon $asOfDate): float
    {
        $account = Account::find($accountId);

        if (!$account) {
            return 0.0;
        }

        // Get sum of all posted journal entries up to the date
        $balance = JournalLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('status', 'posted')
                    ->whereDate('accounting_date', '<=', $asOfDate->toDateString());
            })
            ->selectRaw('SUM(debit_amount) as total_debits, SUM(credit_amount) as total_credits')
            ->first();

        $totalDebits = $balance->total_debits ?? 0;
        $totalCredits = $balance->total_credits ?? 0;

        // Calculate balance based on account type normal balance
        // Assets and Expenses: Debit - Credit
        // Liabilities, Equity, Revenue: Credit - Debit
        if (in_array($account->account_type, ['asset', 'expense'])) {
            return $totalDebits - $totalCredits;
        } else {
            return $totalCredits - $totalDebits;
        }
    }

    /**
     * Determine if account is a current asset
     *
     * @param Account $account
     * @return bool
     */
    private function isCurrentAsset(Account $account): bool
    {
        // Current assets typically have codes starting with 10xx or 11xx
        // Or contain keywords like cash, receivable, inventory
        $code = (int) substr($account->code, 0, 2);

        if ($code >= 10 && $code <= 11) {
            return true;
        }

        $keywords = ['cash', 'bank', 'receivable', 'inventory', 'prepaid'];
        $name = strtolower($account->name);

        foreach ($keywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if account is a current liability
     *
     * @param Account $account
     * @return bool
     */
    private function isCurrentLiability(Account $account): bool
    {
        // Current liabilities typically have codes starting with 20xx or 21xx
        $code = (int) substr($account->code, 0, 2);

        if ($code >= 20 && $code <= 21) {
            return true;
        }

        $keywords = ['payable', 'accrued', 'unearned', 'current', 'short-term'];
        $name = strtolower($account->name);

        foreach ($keywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate retained earnings from inception to date
     *
     * @param Carbon $asOfDate
     * @return float
     */
    private function calculateRetainedEarnings(Carbon $asOfDate): float
    {
        // Get all revenue accounts
        $revenue = Account::where('account_type', 'revenue')
            ->where('is_active', true)
            ->get()
            ->sum(fn($account) => $this->getAccountBalance($account->id, $asOfDate));

        // Get all expense accounts
        $expenses = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->get()
            ->sum(fn($account) => $this->getAccountBalance($account->id, $asOfDate));

        // Retained Earnings = Revenue - Expenses
        return $revenue - $expenses;
    }

    /**
     * Generate comparative balance sheet (two dates)
     *
     * @param Carbon $currentDate
     * @param Carbon $priorDate
     * @return array
     */
    public function generateComparative(Carbon $currentDate, Carbon $priorDate): array
    {
        $current = $this->generate($currentDate);
        $prior = $this->generate($priorDate);

        return [
            'current_period' => $current,
            'prior_period' => $prior,
            'changes' => [
                'assets' => $current['assets']['total_assets'] - $prior['assets']['total_assets'],
                'liabilities' => $current['liabilities']['total_liabilities'] - $prior['liabilities']['total_liabilities'],
                'equity' => $current['equity']['total_equity'] - $prior['equity']['total_equity'],
            ],
        ];
    }
}

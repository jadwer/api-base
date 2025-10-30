<?php

namespace Modules\Reports\Services\FinancialStatements;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;

class TrialBalanceService
{
    /**
     * Generate Trial Balance (Balanza de Comprobación)
     *
     * @param Carbon $asOfDate
     * @return array
     */
    public function generate(Carbon $asOfDate): array
    {
        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();

        $accountBalances = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $asOfDate);

            if ($balance['debit_balance'] != 0 || $balance['credit_balance'] != 0) {
                $accountBalances[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type,
                    'debit_balance' => $balance['debit_balance'],
                    'credit_balance' => $balance['credit_balance'],
                ];

                $totalDebits += $balance['debit_balance'];
                $totalCredits += $balance['credit_balance'];
            }
        }

        $isBalanced = abs($totalDebits - $totalCredits) < 0.01;

        return [
            'as_of_date' => $asOfDate->toDateString(),
            'currency' => config('app.currency', 'MXN'),
            'accounts' => $accountBalances,
            'totals' => [
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'difference' => $totalDebits - $totalCredits,
                'balanced' => $isBalanced,
            ],
            'summary_by_type' => $this->getSummaryByType($accountBalances),
        ];
    }

    /**
     * Get account balance showing debit and credit sides
     *
     * @param int $accountId
     * @param Carbon $asOfDate
     * @return array
     */
    private function getAccountBalance(int $accountId, Carbon $asOfDate): array
    {
        $account = Account::find($accountId);

        if (!$account) {
            return [
                'debit_balance' => 0.0,
                'credit_balance' => 0.0,
            ];
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

        // Calculate net balance
        $netBalance = $totalDebits - $totalCredits;

        // Show balance on appropriate side based on account's normal balance
        if (in_array($account->account_type, ['asset', 'expense'])) {
            // Normal debit balance accounts
            return [
                'debit_balance' => $netBalance > 0 ? $netBalance : 0,
                'credit_balance' => $netBalance < 0 ? abs($netBalance) : 0,
            ];
        } else {
            // Normal credit balance accounts (liability, equity, revenue)
            return [
                'debit_balance' => $netBalance < 0 ? abs($netBalance) : 0,
                'credit_balance' => $netBalance > 0 ? $netBalance : 0,
            ];
        }
    }

    /**
     * Get summary totals by account type
     *
     * @param array $accountBalances
     * @return array
     */
    private function getSummaryByType(array $accountBalances): array
    {
        $summary = [
            'asset' => ['debit' => 0, 'credit' => 0],
            'liability' => ['debit' => 0, 'credit' => 0],
            'equity' => ['debit' => 0, 'credit' => 0],
            'revenue' => ['debit' => 0, 'credit' => 0],
            'expense' => ['debit' => 0, 'credit' => 0],
        ];

        foreach ($accountBalances as $account) {
            $type = $account['account_type'];
            if (isset($summary[$type])) {
                $summary[$type]['debit'] += $account['debit_balance'];
                $summary[$type]['credit'] += $account['credit_balance'];
            }
        }

        return $summary;
    }

    /**
     * Generate comparative trial balance (two dates)
     *
     * @param Carbon $currentDate
     * @param Carbon $priorDate
     * @return array
     */
    public function generateComparative(Carbon $currentDate, Carbon $priorDate): array
    {
        $current = $this->generate($currentDate);
        $prior = $this->generate($priorDate);

        // Match accounts and calculate changes
        $comparativeAccounts = [];
        $currentAccounts = collect($current['accounts'])->keyBy('code');
        $priorAccounts = collect($prior['accounts'])->keyBy('code');

        // Get all unique account codes
        $allCodes = $currentAccounts->keys()->merge($priorAccounts->keys())->unique();

        foreach ($allCodes as $code) {
            $currentAccount = $currentAccounts->get($code, [
                'code' => $code,
                'name' => '',
                'account_type' => '',
                'debit_balance' => 0,
                'credit_balance' => 0,
            ]);

            $priorAccount = $priorAccounts->get($code, [
                'code' => $code,
                'name' => '',
                'account_type' => '',
                'debit_balance' => 0,
                'credit_balance' => 0,
            ]);

            $comparativeAccounts[] = [
                'code' => $code,
                'name' => $currentAccount['name'] ?: $priorAccount['name'],
                'account_type' => $currentAccount['account_type'] ?: $priorAccount['account_type'],
                'current_debit' => $currentAccount['debit_balance'],
                'current_credit' => $currentAccount['credit_balance'],
                'prior_debit' => $priorAccount['debit_balance'],
                'prior_credit' => $priorAccount['credit_balance'],
                'debit_change' => $currentAccount['debit_balance'] - $priorAccount['debit_balance'],
                'credit_change' => $currentAccount['credit_balance'] - $priorAccount['credit_balance'],
            ];
        }

        return [
            'current_period' => $current,
            'prior_period' => $prior,
            'comparative_accounts' => $comparativeAccounts,
            'changes' => [
                'total_debits' => $current['totals']['total_debits'] - $prior['totals']['total_debits'],
                'total_credits' => $current['totals']['total_credits'] - $prior['totals']['total_credits'],
            ],
        ];
    }

    /**
     * Generate detailed trial balance with period activity
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateDetailed(Carbon $startDate, Carbon $endDate): array
    {
        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();

        $accountBalances = [];
        $totalBeginningDebits = 0;
        $totalBeginningCredits = 0;
        $totalPeriodDebits = 0;
        $totalPeriodCredits = 0;
        $totalEndingDebits = 0;
        $totalEndingCredits = 0;

        foreach ($accounts as $account) {
            $beginningBalance = $this->getAccountBalance($account->id, $startDate->copy()->subDay());
            $periodActivity = $this->getPeriodActivity($account->id, $startDate, $endDate);
            $endingBalance = $this->getAccountBalance($account->id, $endDate);

            if ($beginningBalance['debit_balance'] != 0
                || $beginningBalance['credit_balance'] != 0
                || $periodActivity['debits'] != 0
                || $periodActivity['credits'] != 0
                || $endingBalance['debit_balance'] != 0
                || $endingBalance['credit_balance'] != 0
            ) {
                $accountBalances[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type,
                    'beginning_debit' => $beginningBalance['debit_balance'],
                    'beginning_credit' => $beginningBalance['credit_balance'],
                    'period_debits' => $periodActivity['debits'],
                    'period_credits' => $periodActivity['credits'],
                    'ending_debit' => $endingBalance['debit_balance'],
                    'ending_credit' => $endingBalance['credit_balance'],
                ];

                $totalBeginningDebits += $beginningBalance['debit_balance'];
                $totalBeginningCredits += $beginningBalance['credit_balance'];
                $totalPeriodDebits += $periodActivity['debits'];
                $totalPeriodCredits += $periodActivity['credits'];
                $totalEndingDebits += $endingBalance['debit_balance'];
                $totalEndingCredits += $endingBalance['credit_balance'];
            }
        }

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'currency' => config('app.currency', 'MXN'),
            'accounts' => $accountBalances,
            'totals' => [
                'beginning_debits' => $totalBeginningDebits,
                'beginning_credits' => $totalBeginningCredits,
                'period_debits' => $totalPeriodDebits,
                'period_credits' => $totalPeriodCredits,
                'ending_debits' => $totalEndingDebits,
                'ending_credits' => $totalEndingCredits,
                'beginning_balanced' => abs($totalBeginningDebits - $totalBeginningCredits) < 0.01,
                'period_balanced' => abs($totalPeriodDebits - $totalPeriodCredits) < 0.01,
                'ending_balanced' => abs($totalEndingDebits - $totalEndingCredits) < 0.01,
            ],
        ];
    }

    /**
     * Get period activity (debits and credits) for an account
     *
     * @param int $accountId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getPeriodActivity(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $activity = JournalLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereDate('accounting_date', '>=', $startDate->toDateString())
                    ->whereDate('accounting_date', '<=', $endDate->toDateString());
            })
            ->selectRaw('SUM(debit_amount) as total_debits, SUM(credit_amount) as total_credits')
            ->first();

        return [
            'debits' => $activity->total_debits ?? 0,
            'credits' => $activity->total_credits ?? 0,
        ];
    }
}

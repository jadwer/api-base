<?php

namespace Modules\Reports\Services\FinancialStatements;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;

class IncomeStatementService
{
    /**
     * Generate Income Statement (Estado de Resultados)
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generate(Carbon $startDate, Carbon $endDate): array
    {
        $revenue = $this->calculateRevenue($startDate, $endDate);
        $costOfGoodsSold = $this->calculateCostOfGoodsSold($startDate, $endDate);
        $grossProfit = $revenue['total_revenue'] - $costOfGoodsSold;

        $operatingExpenses = $this->calculateOperatingExpenses($startDate, $endDate);
        $operatingIncome = $grossProfit - $operatingExpenses['total_operating_expenses'];

        $otherIncomeExpenses = $this->calculateOtherIncomeExpenses($startDate, $endDate);
        $netIncome = $operatingIncome + $otherIncomeExpenses['net_other'];

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'currency' => config('app.currency', 'MXN'),
            'revenue' => $revenue,
            'cost_of_goods_sold' => $costOfGoodsSold,
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $revenue['total_revenue'] > 0
                ? round(($grossProfit / $revenue['total_revenue']) * 100, 2)
                : 0,
            'operating_expenses' => $operatingExpenses,
            'operating_income' => $operatingIncome,
            'operating_margin' => $revenue['total_revenue'] > 0
                ? round(($operatingIncome / $revenue['total_revenue']) * 100, 2)
                : 0,
            'other_income_expenses' => $otherIncomeExpenses,
            'net_income' => $netIncome,
            'net_profit_margin' => $revenue['total_revenue'] > 0
                ? round(($netIncome / $revenue['total_revenue']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Calculate Revenue section
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateRevenue(Carbon $startDate, Carbon $endDate): array
    {
        $revenueAccounts = Account::where('account_type', 'revenue')
            ->where('is_active', true)
            ->get();

        $revenueItems = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $account) {
            $balance = $this->getAccountBalanceForPeriod($account->id, $startDate, $endDate);

            if ($balance != 0) {
                $revenueItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $balance,
                ];
                $totalRevenue += $balance;
            }
        }

        return [
            'accounts' => $revenueItems,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * Calculate Cost of Goods Sold
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateCostOfGoodsSold(Carbon $startDate, Carbon $endDate): float
    {
        // COGS accounts typically have codes starting with 5xxx or contain "cost of" in name
        $cogsAccounts = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '5%')
                    ->orWhere('name', 'like', '%cost of%')
                    ->orWhere('name', 'like', '%costo de%');
            })
            ->get();

        $totalCogs = 0;

        foreach ($cogsAccounts as $account) {
            $totalCogs += $this->getAccountBalanceForPeriod($account->id, $startDate, $endDate);
        }

        return $totalCogs;
    }

    /**
     * Calculate Operating Expenses section
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateOperatingExpenses(Carbon $startDate, Carbon $endDate): array
    {
        // Operating expenses are expenses that are NOT COGS
        $expenseAccounts = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'not like', '5%')
                    ->where('name', 'not like', '%cost of%')
                    ->where('name', 'not like', '%costo de%');
            })
            ->get();

        $expenseItems = [];
        $totalExpenses = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->getAccountBalanceForPeriod($account->id, $startDate, $endDate);

            if ($balance != 0) {
                $expenseItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $balance,
                ];
                $totalExpenses += $balance;
            }
        }

        return [
            'accounts' => $expenseItems,
            'total_operating_expenses' => $totalExpenses,
        ];
    }

    /**
     * Calculate Other Income/Expenses
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateOtherIncomeExpenses(Carbon $startDate, Carbon $endDate): array
    {
        // Other income/expenses like interest, gains/losses
        // For now, return empty structure - can be enhanced later
        return [
            'other_income' => 0,
            'other_expenses' => 0,
            'net_other' => 0,
        ];
    }

    /**
     * Get account balance for specific period
     *
     * @param int $accountId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function getAccountBalanceForPeriod(int $accountId, Carbon $startDate, Carbon $endDate): float
    {
        $account = Account::find($accountId);

        if (!$account) {
            return 0.0;
        }

        // Get sum of posted journal entries within the period
        $balance = JournalLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereDate('accounting_date', '>=', $startDate->toDateString())
                    ->whereDate('accounting_date', '<=', $endDate->toDateString());
            })
            ->selectRaw('SUM(debit_amount) as total_debits, SUM(credit_amount) as total_credits')
            ->first();

        $totalDebits = $balance->total_debits ?? 0;
        $totalCredits = $balance->total_credits ?? 0;

        // Calculate balance based on account type normal balance
        // Revenue: Credit - Debit (normal credit balance)
        // Expenses: Debit - Credit (normal debit balance)
        if ($account->account_type === 'revenue') {
            return $totalCredits - $totalDebits;
        } else {
            return $totalDebits - $totalCredits;
        }
    }

    /**
     * Generate comparative income statement (two periods)
     *
     * @param Carbon $currentStartDate
     * @param Carbon $currentEndDate
     * @param Carbon $priorStartDate
     * @param Carbon $priorEndDate
     * @return array
     */
    public function generateComparative(
        Carbon $currentStartDate,
        Carbon $currentEndDate,
        Carbon $priorStartDate,
        Carbon $priorEndDate
    ): array {
        $current = $this->generate($currentStartDate, $currentEndDate);
        $prior = $this->generate($priorStartDate, $priorEndDate);

        return [
            'current_period' => $current,
            'prior_period' => $prior,
            'changes' => [
                'revenue' => $current['revenue']['total_revenue'] - $prior['revenue']['total_revenue'],
                'revenue_growth_percentage' => $prior['revenue']['total_revenue'] > 0
                    ? round((($current['revenue']['total_revenue'] - $prior['revenue']['total_revenue']) / $prior['revenue']['total_revenue']) * 100, 2)
                    : 0,
                'gross_profit' => $current['gross_profit'] - $prior['gross_profit'],
                'operating_income' => $current['operating_income'] - $prior['operating_income'],
                'net_income' => $current['net_income'] - $prior['net_income'],
                'net_income_growth_percentage' => $prior['net_income'] > 0
                    ? round((($current['net_income'] - $prior['net_income']) / $prior['net_income']) * 100, 2)
                    : 0,
            ],
        ];
    }
}

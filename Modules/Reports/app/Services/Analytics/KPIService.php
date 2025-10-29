<?php

namespace Modules\Reports\Services\Analytics;

use Modules\Sales\Models\SalesOrder;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Inventory\Models\Stock;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KPIService
{
    /**
     * Get all key performance indicators
     *
     * @return array
     */
    public function getKPIs(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        return [
            'revenue' => $this->getRevenueKPI($currentMonth, $previousMonth, $previousMonthEnd),
            'gross_profit_margin' => $this->getGrossProfitMargin(),
            'ar_turnover_days' => $this->getARTurnoverDays(),
            'ap_turnover_days' => $this->getAPTurnoverDays(),
            'inventory_turnover' => $this->getInventoryTurnover(),
            'current_ratio' => $this->getCurrentRatio(),
            'quick_ratio' => $this->getQuickRatio(),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Get revenue KPI with growth
     *
     * @param Carbon $currentMonth
     * @param Carbon $previousMonth
     * @param Carbon $previousMonthEnd
     * @return array
     */
    private function getRevenueKPI(Carbon $currentMonth, Carbon $previousMonth, Carbon $previousMonthEnd): array
    {
        $currentRevenue = SalesOrder::whereBetween('order_date', [
            $currentMonth->toDateString(),
            Carbon::now()->toDateString()
        ])
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('total_amount');

        $previousRevenue = SalesOrder::whereBetween('order_date', [
            $previousMonth->toDateString(),
            $previousMonthEnd->toDateString()
        ])
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('total_amount');

        $growthPercentage = $previousRevenue > 0
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : 0;

        return [
            'current_month' => $currentRevenue,
            'previous_month' => $previousRevenue,
            'growth_percentage' => $growthPercentage,
        ];
    }

    /**
     * Get gross profit margin
     *
     * @return float
     */
    private function getGrossProfitMargin(): float
    {
        $startDate = Carbon::now()->startOfYear();
        $endDate = Carbon::now();

        // Calculate revenue
        $revenue = $this->getAccountTypeBalance('revenue', $startDate, $endDate);

        // Calculate COGS
        $cogs = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '5%')
                    ->orWhere('name', 'like', '%cost of%')
                    ->orWhere('name', 'like', '%costo de%');
            })
            ->get()
            ->sum(fn($account) => $this->getAccountBalanceForPeriod($account->id, $startDate, $endDate));

        $grossProfit = $revenue - $cogs;

        return $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0;
    }

    /**
     * Get AR turnover days (average collection period)
     *
     * @return float
     */
    private function getARTurnoverDays(): float
    {
        $averageAR = ARInvoice::where('status', '!=', 'draft')
            ->where('status', '!=', 'voided')
            ->avg(DB::raw('total_amount - paid_amount'));

        $startDate = Carbon::now()->startOfYear();
        $revenue = $this->getAccountTypeBalance('revenue', $startDate, Carbon::now());
        $dailyRevenue = $revenue / Carbon::now()->diffInDays($startDate);

        return $dailyRevenue > 0 ? round($averageAR / $dailyRevenue, 0) : 0;
    }

    /**
     * Get AP turnover days (average payment period)
     *
     * @return float
     */
    private function getAPTurnoverDays(): float
    {
        $averageAP = APInvoice::where('status', '!=', 'draft')
            ->where('status', '!=', 'voided')
            ->avg(DB::raw('total_amount - paid_amount'));

        $startDate = Carbon::now()->startOfYear();
        $expenses = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->get()
            ->sum(fn($account) => $this->getAccountBalanceForPeriod($account->id, $startDate, Carbon::now()));

        $dailyExpenses = $expenses / Carbon::now()->diffInDays($startDate);

        return $dailyExpenses > 0 ? round($averageAP / $dailyExpenses, 0) : 0;
    }

    /**
     * Get inventory turnover ratio
     *
     * @return float
     */
    private function getInventoryTurnover(): float
    {
        $startDate = Carbon::now()->startOfYear();

        // COGS
        $cogs = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '5%')
                    ->orWhere('name', 'like', '%cost of%');
            })
            ->get()
            ->sum(fn($account) => $this->getAccountBalanceForPeriod($account->id, $startDate, Carbon::now()));

        // Average inventory value
        $averageInventory = Stock::avg(DB::raw('quantity_on_hand * unit_cost'));

        return $averageInventory > 0 ? round($cogs / $averageInventory, 2) : 0;
    }

    /**
     * Get current ratio (current assets / current liabilities)
     *
     * @return float
     */
    private function getCurrentRatio(): float
    {
        $asOfDate = Carbon::now();

        // Current assets
        $currentAssets = Account::where('account_type', 'asset')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '10%')
                    ->orWhere('code', 'like', '11%');
            })
            ->get()
            ->sum(fn($account) => $this->getAccountBalanceAsOfDate($account->id, $asOfDate));

        // Current liabilities
        $currentLiabilities = Account::where('account_type', 'liability')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '20%')
                    ->orWhere('code', 'like', '21%');
            })
            ->get()
            ->sum(fn($account) => $this->getAccountBalanceAsOfDate($account->id, $asOfDate));

        return $currentLiabilities > 0 ? round($currentAssets / $currentLiabilities, 2) : 0;
    }

    /**
     * Get quick ratio ((current assets - inventory) / current liabilities)
     *
     * @return float
     */
    private function getQuickRatio(): float
    {
        $asOfDate = Carbon::now();

        // Current assets excluding inventory
        $quickAssets = Account::where('account_type', 'asset')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '10%')
                    ->orWhere('code', 'like', '11%');
            })
            ->where('name', 'not like', '%inventory%')
            ->where('name', 'not like', '%inventario%')
            ->get()
            ->sum(fn($account) => $this->getAccountBalanceAsOfDate($account->id, $asOfDate));

        // Current liabilities
        $currentLiabilities = Account::where('account_type', 'liability')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '20%')
                    ->orWhere('code', 'like', '21%');
            })
            ->get()
            ->sum(fn($account) => $this->getAccountBalanceAsOfDate($account->id, $asOfDate));

        return $currentLiabilities > 0 ? round($quickAssets / $currentLiabilities, 2) : 0;
    }

    /**
     * Get account type balance for period
     *
     * @param string $accountType
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function getAccountTypeBalance(string $accountType, Carbon $startDate, Carbon $endDate): float
    {
        $accounts = Account::where('account_type', $accountType)
            ->where('is_active', true)
            ->get();

        return $accounts->sum(fn($account) => $this->getAccountBalanceForPeriod($account->id, $startDate, $endDate));
    }

    /**
     * Get account balance for period
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

        if ($account->account_type === 'revenue') {
            return $totalCredits - $totalDebits;
        } else {
            return $totalDebits - $totalCredits;
        }
    }

    /**
     * Get account balance as of date
     *
     * @param int $accountId
     * @param Carbon $asOfDate
     * @return float
     */
    private function getAccountBalanceAsOfDate(int $accountId, Carbon $asOfDate): float
    {
        $account = Account::find($accountId);
        if (!$account) {
            return 0.0;
        }

        $balance = JournalLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('status', 'posted')
                    ->whereDate('accounting_date', '<=', $asOfDate->toDateString());
            })
            ->selectRaw('SUM(debit_amount) as total_debits, SUM(credit_amount) as total_credits')
            ->first();

        $totalDebits = $balance->total_debits ?? 0;
        $totalCredits = $balance->total_credits ?? 0;

        if (in_array($account->account_type, ['asset', 'expense'])) {
            return $totalDebits - $totalCredits;
        } else {
            return $totalCredits - $totalDebits;
        }
    }
}

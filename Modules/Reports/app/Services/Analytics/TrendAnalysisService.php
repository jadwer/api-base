<?php

namespace Modules\Reports\Services\Analytics;

use Modules\Sales\Models\SalesOrder;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrendAnalysisService
{
    /**
     * Generate trend analysis for a specific metric
     *
     * @param string $metric (revenue, sales, purchases, expenses, profit)
     * @param string $period (6months, 12months, 24months)
     * @return array
     */
    public function generateTrend(string $metric, string $period = '12months'): array
    {
        $months = match ($period) {
            '6months' => 6,
            '24months' => 24,
            default => 12,
        };

        $data = match ($metric) {
            'revenue' => $this->getRevenueTrend($months),
            'sales' => $this->getSalesTrend($months),
            'purchases' => $this->getPurchaseTrend($months),
            'expenses' => $this->getExpensesTrend($months),
            'profit' => $this->getProfitTrend($months),
            default => [],
        };

        return [
            'metric' => $metric,
            'period' => $period,
            'months_count' => $months,
            'data' => $data,
            'trend' => $this->calculateTrend($data),
            'growth_rate' => $this->calculateGrowthRate($data),
        ];
    }

    /**
     * Get revenue trend
     *
     * @param int $months
     * @return array
     */
    private function getRevenueTrend(int $months): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $revenue = $this->getRevenueForPeriod($startDate, $endDate);

            $data[] = [
                'month' => $startDate->format('Y-m'),
                'month_name' => $startDate->format('M Y'),
                'value' => $revenue,
            ];
        }

        return $data;
    }

    /**
     * Get sales trend
     *
     * @param int $months
     * @return array
     */
    private function getSalesTrend(int $months): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $salesAmount = SalesOrder::whereBetween('order_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('total_amount');

            $data[] = [
                'month' => $startDate->format('Y-m'),
                'month_name' => $startDate->format('M Y'),
                'value' => $salesAmount,
            ];
        }

        return $data;
    }

    /**
     * Get purchase trend
     *
     * @param int $months
     * @return array
     */
    private function getPurchaseTrend(int $months): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $purchaseAmount = PurchaseOrder::whereBetween('order_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
                ->whereIn('status', ['approved', 'received'])
                ->sum('total_amount');

            $data[] = [
                'month' => $startDate->format('Y-m'),
                'month_name' => $startDate->format('M Y'),
                'value' => $purchaseAmount,
            ];
        }

        return $data;
    }

    /**
     * Get expenses trend
     *
     * @param int $months
     * @return array
     */
    private function getExpensesTrend(int $months): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $expenses = $this->getExpensesForPeriod($startDate, $endDate);

            $data[] = [
                'month' => $startDate->format('Y-m'),
                'month_name' => $startDate->format('M Y'),
                'value' => $expenses,
            ];
        }

        return $data;
    }

    /**
     * Get profit trend
     *
     * @param int $months
     * @return array
     */
    private function getProfitTrend(int $months): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();

            $revenue = $this->getRevenueForPeriod($startDate, $endDate);
            $expenses = $this->getExpensesForPeriod($startDate, $endDate);
            $profit = $revenue - $expenses;

            $data[] = [
                'month' => $startDate->format('Y-m'),
                'month_name' => $startDate->format('M Y'),
                'value' => $profit,
            ];
        }

        return $data;
    }

    /**
     * Get revenue for specific period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function getRevenueForPeriod(Carbon $startDate, Carbon $endDate): float
    {
        $revenueAccounts = Account::where('account_type', 'revenue')
            ->where('is_active', true)
            ->pluck('id');

        $totalRevenue = 0;

        foreach ($revenueAccounts as $accountId) {
            $balance = JournalLine::where('account_id', $accountId)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'posted')
                        ->whereDate('accounting_date', '>=', $startDate->toDateString())
                        ->whereDate('accounting_date', '<=', $endDate->toDateString());
                })
                ->selectRaw('SUM(credit_amount) - SUM(debit_amount) as net_balance')
                ->value('net_balance');

            $totalRevenue += $balance ?? 0;
        }

        return $totalRevenue;
    }

    /**
     * Get expenses for specific period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function getExpensesForPeriod(Carbon $startDate, Carbon $endDate): float
    {
        $expenseAccounts = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->pluck('id');

        $totalExpenses = 0;

        foreach ($expenseAccounts as $accountId) {
            $balance = JournalLine::where('account_id', $accountId)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'posted')
                        ->whereDate('accounting_date', '>=', $startDate->toDateString())
                        ->whereDate('accounting_date', '<=', $endDate->toDateString());
                })
                ->selectRaw('SUM(debit_amount) - SUM(credit_amount) as net_balance')
                ->value('net_balance');

            $totalExpenses += $balance ?? 0;
        }

        return $totalExpenses;
    }

    /**
     * Calculate trend direction (increasing, decreasing, stable)
     *
     * @param array $data
     * @return string
     */
    private function calculateTrend(array $data): string
    {
        if (count($data) < 2) {
            return 'insufficient_data';
        }

        $values = collect($data)->pluck('value')->all();
        $firstHalf = array_slice($values, 0, (int) ceil(count($values) / 2));
        $secondHalf = array_slice($values, (int) floor(count($values) / 2));

        $firstAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
        $secondAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;

        $change = $firstAvg > 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;

        if (abs($change) < 5) {
            return 'stable';
        }

        return $change > 0 ? 'increasing' : 'decreasing';
    }

    /**
     * Calculate average growth rate
     *
     * @param array $data
     * @return float
     */
    private function calculateGrowthRate(array $data): float
    {
        if (count($data) < 2) {
            return 0;
        }

        $values = collect($data)->pluck('value')->all();
        $growthRates = [];

        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] > 0) {
                $growthRates[] = (($values[$i] - $values[$i - 1]) / $values[$i - 1]) * 100;
            }
        }

        return count($growthRates) > 0
            ? round(array_sum($growthRates) / count($growthRates), 2)
            : 0;
    }
}

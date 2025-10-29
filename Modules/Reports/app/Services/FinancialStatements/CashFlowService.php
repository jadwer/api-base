<?php

namespace Modules\Reports\Services\FinancialStatements;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;

class CashFlowService
{
    /**
     * Generate Cash Flow Statement (Flujo de Efectivo)
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generate(Carbon $startDate, Carbon $endDate): array
    {
        $operatingActivities = $this->calculateOperatingActivities($startDate, $endDate);
        $investingActivities = $this->calculateInvestingActivities($startDate, $endDate);
        $financingActivities = $this->calculateFinancingActivities($startDate, $endDate);

        $netCashChange = $operatingActivities['net_cash_from_operations']
            + $investingActivities['net_cash_from_investing']
            + $financingActivities['net_cash_from_financing'];

        $beginningCash = $this->getCashBalance($startDate->copy()->subDay());
        $endingCash = $this->getCashBalance($endDate);

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'currency' => config('app.currency', 'MXN'),
            'operating_activities' => $operatingActivities,
            'investing_activities' => $investingActivities,
            'financing_activities' => $financingActivities,
            'net_change_in_cash' => $netCashChange,
            'beginning_cash' => $beginningCash,
            'ending_cash' => $endingCash,
            'reconciliation_difference' => abs($endingCash - ($beginningCash + $netCashChange)),
        ];
    }

    /**
     * Calculate Operating Activities section
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateOperatingActivities(Carbon $startDate, Carbon $endDate): array
    {
        // Operating activities include cash from customers and payments to suppliers/employees
        // We'll use a simplified approach based on cash accounts movement related to operations

        $cashAccounts = Account::where('account_type', 'asset')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '1010%')
                    ->orWhere('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%efectivo%')
                    ->orWhere('name', 'like', '%banco%')
                    ->orWhere('name', 'like', '%bank%');
            })
            ->pluck('id');

        $cashReceipts = 0;
        $cashPayments = 0;

        foreach ($cashAccounts as $accountId) {
            $transactions = JournalLine::where('account_id', $accountId)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'posted')
                        ->whereDate('accounting_date', '>=', $startDate->toDateString())
                        ->whereDate('accounting_date', '<=', $endDate->toDateString());
                })
                ->with('journalEntry')
                ->get();

            foreach ($transactions as $line) {
                // Classify based on description or related accounts
                $isOperating = $this->isOperatingActivity($line);

                if ($isOperating) {
                    if ($line->debit_amount > 0) {
                        $cashReceipts += $line->debit_amount;
                    } else {
                        $cashPayments += $line->credit_amount;
                    }
                }
            }
        }

        $netCashFromOperations = $cashReceipts - $cashPayments;

        return [
            'cash_receipts_from_customers' => $cashReceipts,
            'cash_payments_to_suppliers_employees' => -$cashPayments,
            'net_cash_from_operations' => $netCashFromOperations,
        ];
    }

    /**
     * Calculate Investing Activities section
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateInvestingActivities(Carbon $startDate, Carbon $endDate): array
    {
        // Investing activities include purchase/sale of long-term assets
        $fixedAssetAccounts = Account::where('account_type', 'asset')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '15%')
                    ->orWhere('code', 'like', '16%')
                    ->orWhere('name', 'like', '%fixed asset%')
                    ->orWhere('name', 'like', '%equipment%')
                    ->orWhere('name', 'like', '%property%')
                    ->orWhere('name', 'like', '%activo fijo%');
            })
            ->get();

        $assetPurchases = 0;
        $assetSales = 0;

        foreach ($fixedAssetAccounts as $account) {
            $balance = $this->getAccountBalanceForPeriod($account->id, $startDate, $endDate);

            if ($balance > 0) {
                $assetPurchases += $balance;
            } else {
                $assetSales += abs($balance);
            }
        }

        $netCashFromInvesting = $assetSales - $assetPurchases;

        return [
            'asset_purchases' => -$assetPurchases,
            'asset_sales' => $assetSales,
            'net_cash_from_investing' => $netCashFromInvesting,
        ];
    }

    /**
     * Calculate Financing Activities section
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateFinancingActivities(Carbon $startDate, Carbon $endDate): array
    {
        // Financing activities include loans, equity contributions
        $liabilityAccounts = Account::where('account_type', 'liability')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '22%')
                    ->orWhere('code', 'like', '23%')
                    ->orWhere('name', 'like', '%loan%')
                    ->orWhere('name', 'like', '%debt%')
                    ->orWhere('name', 'like', '%préstamo%');
            })
            ->get();

        $loansReceived = 0;
        $loansRepaid = 0;

        foreach ($liabilityAccounts as $account) {
            $balance = $this->getAccountBalanceForPeriod($account->id, $startDate, $endDate);

            if ($balance > 0) {
                $loansReceived += $balance;
            } else {
                $loansRepaid += abs($balance);
            }
        }

        $netCashFromFinancing = $loansReceived - $loansRepaid;

        return [
            'loans_received' => $loansReceived,
            'loans_repaid' => -$loansRepaid,
            'net_cash_from_financing' => $netCashFromFinancing,
        ];
    }

    /**
     * Get cash balance as of specific date
     *
     * @param Carbon $asOfDate
     * @return float
     */
    private function getCashBalance(Carbon $asOfDate): float
    {
        $cashAccounts = Account::where('account_type', 'asset')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '1010%')
                    ->orWhere('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%efectivo%')
                    ->orWhere('name', 'like', '%banco%')
                    ->orWhere('name', 'like', '%bank%');
            })
            ->get();

        $totalCash = 0;

        foreach ($cashAccounts as $account) {
            $balance = JournalLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                    $query->where('status', 'posted')
                        ->whereDate('accounting_date', '<=', $asOfDate->toDateString());
                })
                ->selectRaw('SUM(debit_amount) as total_debits, SUM(credit_amount) as total_credits')
                ->first();

            $totalDebits = $balance->total_debits ?? 0;
            $totalCredits = $balance->total_credits ?? 0;

            // Cash is an asset, so debit increases, credit decreases
            $totalCash += ($totalDebits - $totalCredits);
        }

        return $totalCash;
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

        // Assets: Debit - Credit
        // Liabilities: Credit - Debit
        if ($account->account_type === 'asset') {
            return $totalDebits - $totalCredits;
        } else {
            return $totalCredits - $totalDebits;
        }
    }

    /**
     * Determine if journal line represents an operating activity
     *
     * @param JournalLine $line
     * @return bool
     */
    private function isOperatingActivity(JournalLine $line): bool
    {
        $description = strtolower($line->journalEntry->description ?? '');

        // Exclude investing and financing activities
        $investingKeywords = ['asset purchase', 'equipment', 'property', 'fixed asset'];
        $financingKeywords = ['loan', 'debt', 'equity', 'dividend'];

        foreach ($investingKeywords as $keyword) {
            if (str_contains($description, $keyword)) {
                return false;
            }
        }

        foreach ($financingKeywords as $keyword) {
            if (str_contains($description, $keyword)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate comparative cash flow statement (two periods)
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
                'operating_activities' => $current['operating_activities']['net_cash_from_operations']
                    - $prior['operating_activities']['net_cash_from_operations'],
                'investing_activities' => $current['investing_activities']['net_cash_from_investing']
                    - $prior['investing_activities']['net_cash_from_investing'],
                'financing_activities' => $current['financing_activities']['net_cash_from_financing']
                    - $prior['financing_activities']['net_cash_from_financing'],
                'net_change' => $current['net_change_in_cash'] - $prior['net_change_in_cash'],
            ],
        ];
    }
}

<?php

namespace Modules\Accounting\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;

class ReportController extends JsonApiController
{
    /**
     * Balance General (Balance Sheet)
     */
    public function balanceGeneral(Request $request): JsonResponse
    {
        $endDate = $request->input('end_date', now());
        
        // Get account balances by type
        $assets = $this->getAccountBalances('asset', $endDate);
        $liabilities = $this->getAccountBalances('liability', $endDate);
        $equity = $this->getAccountBalances('equity', $endDate);
        
        $totalAssets = collect($assets)->sum('balance');
        $totalLiabilities = collect($liabilities)->sum('balance');
        $totalEquity = collect($equity)->sum('balance');
        
        return response()->json([
            'report_type' => 'balance_general',
            'report_date' => $endDate,
            'data' => [
                'assets' => [
                    'accounts' => $assets,
                    'total' => $totalAssets
                ],
                'liabilities' => [
                    'accounts' => $liabilities,
                    'total' => $totalLiabilities
                ],
                'equity' => [
                    'accounts' => $equity,
                    'total' => $totalEquity
                ]
            ],
            'totals' => [
                'total_assets' => $totalAssets,
                'total_liabilities_equity' => $totalLiabilities + $totalEquity,
                'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01
            ]
        ]);
    }

    /**
     * Estado de Resultados (Income Statement)
     */
    public function estadoResultados(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now());
        
        // Get revenue and expense account balances for the period
        $revenue = $this->getAccountBalances('revenue', $endDate, $startDate);
        $expenses = $this->getAccountBalances('expense', $endDate, $startDate);
        
        $totalRevenue = collect($revenue)->sum('balance');
        $totalExpenses = collect($expenses)->sum('balance');
        $netIncome = $totalRevenue - $totalExpenses;
        
        return response()->json([
            'report_type' => 'estado_resultados',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => [
                'revenue' => [
                    'accounts' => $revenue,
                    'total' => $totalRevenue
                ],
                'expenses' => [
                    'accounts' => $expenses,
                    'total' => $totalExpenses
                ],
                'net_income' => $netIncome
            ]
        ]);
    }

    /**
     * Balanza de Comprobación (Trial Balance)
     */
    public function balanzaComprobacion(Request $request): JsonResponse
    {
        $endDate = $request->input('end_date', now());
        
        $accounts = Account::where('status', 'active')
            ->where('is_postable', true)
            ->get();
        
        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;
        
        foreach ($accounts as $account) {
            $debits = JournalLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function($query) use ($endDate) {
                    $query->where('date', '<=', $endDate);
                })
                ->sum('debit');
            
            $credits = JournalLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function($query) use ($endDate) {
                    $query->where('date', '<=', $endDate);
                })
                ->sum('credit');
            
            $balance = $debits - $credits;
            
            if ($debits > 0 || $credits > 0) {
                $trialBalance[] = [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->account_type,
                    'debits' => $debits,
                    'credits' => $credits,
                    'balance' => $balance
                ];
                
                $totalDebits += $debits;
                $totalCredits += $credits;
            }
        }
        
        return response()->json([
            'report_type' => 'balanza_comprobacion',
            'report_date' => $endDate,
            'data' => $trialBalance,
            'totals' => [
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'balanced' => abs($totalDebits - $totalCredits) < 0.01
            ]
        ]);
    }

    /**
     * Libro Diario (General Journal)
     */
    public function libroDiario(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now());
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 50);
        
        $journalEntries = JournalEntry::with(['journalLines.account'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
        
        $entries = $journalEntries->getCollection()->map(function($entry) {
            return [
                'entry_number' => $entry->number,
                'entry_date' => $entry->date,
                'description' => $entry->description,
                'reference' => $entry->reference,
                'lines' => $entry->journalLines->map(function($line) {
                    return [
                        'account_code' => $line->account->code,
                        'account_name' => $line->account->name,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                        'memo' => $line->memo
                    ];
                }),
                'total_debits' => $entry->journalLines->sum('debit'),
                'total_credits' => $entry->journalLines->sum('credit')
            ];
        });
        
        return response()->json([
            'report_type' => 'libro_diario',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => $entries,
            'pagination' => [
                'current_page' => $journalEntries->currentPage(),
                'per_page' => $journalEntries->perPage(),
                'total' => $journalEntries->total(),
                'last_page' => $journalEntries->lastPage()
            ]
        ]);
    }

    /**
     * Libro Mayor (General Ledger)
     */
    public function libroMayor(Request $request): JsonResponse
    {
        $accountId = $request->input('account_id');
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now());
        
        if (!$accountId) {
            return response()->json(['error' => 'account_id is required'], 400);
        }
        
        $account = Account::find($accountId);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }
        
        // Get opening balance
        $openingBalance = JournalLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function($query) use ($startDate) {
                $query->where('date', '<', $startDate);
            })
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance') ?? 0;
        
        // Get transactions for the period
        $transactions = JournalLine::with(['journalEntry'])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->orderBy('created_at')
            ->get();
        
        $runningBalance = $openingBalance;
        $ledgerLines = [];
        
        foreach ($transactions as $transaction) {
            $runningBalance += $transaction->debit - $transaction->credit;
            
            $ledgerLines[] = [
                'entry_date' => $transaction->journalEntry->date,
                'entry_number' => $transaction->journalEntry->number,
                'description' => $transaction->journalEntry->description,
                'reference' => $transaction->journalEntry->reference,
                'memo' => $transaction->memo,
                'debit' => $transaction->debit,
                'credit' => $transaction->credit,
                'balance' => $runningBalance
            ];
        }
        
        return response()->json([
            'report_type' => 'libro_mayor',
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->account_type
            ],
            'period_start' => $startDate,
            'period_end' => $endDate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'data' => $ledgerLines
        ]);
    }

    /**
     * Get account balances by type
     */
    private function getAccountBalances(string $accountType, $endDate, $startDate = null): array
    {
        $accounts = Account::where('account_type', $accountType)
            ->where('status', 'active')
            ->where('is_postable', true)
            ->get();
        
        $balances = [];
        
        foreach ($accounts as $account) {
            $query = JournalLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function($entryQuery) use ($endDate, $startDate) {
                    $entryQuery->where('date', '<=', $endDate);
                    if ($startDate) {
                        $entryQuery->where('date', '>=', $startDate);
                    }
                });
            
            $debits = (clone $query)->sum('debit');
            $credits = (clone $query)->sum('credit');
            
            $balance = match($accountType) {
                'asset', 'expense' => $debits - $credits,
                'liability', 'equity', 'revenue' => $credits - $debits,
                default => $debits - $credits
            };
            
            if (abs($balance) > 0.01) { // Only include accounts with significant balances
                $balances[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->account_type,
                    'balance' => $balance,
                    'debits' => $debits,
                    'credits' => $credits
                ];
            }
        }
        
        return $balances;
    }
}
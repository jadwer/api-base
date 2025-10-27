<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankTransaction;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\Account;
use Carbon\Carbon;

class BankReconciliationService
{
    /**
     * Auto-reconcile bank transactions with GL entries
     *
     * @param BankAccount $account
     * @param Carbon $date
     * @return array
     */
    public function autoReconcile(BankAccount $account, Carbon $date): array
    {
        // Get bank transactions that need reconciliation
        $bankTransactions = $this->getBankTransactions($account, $date);

        // Get GL transactions for the bank account
        $glTransactions = $this->getGLTransactions($account, $date);

        $matches = collect();
        $unmatched = collect();
        $unmatchedGL = $glTransactions;

        foreach ($bankTransactions as $bankTx) {
            $match = $this->findMatch($bankTx, $unmatchedGL);

            if ($match) {
                $matches->push([
                    'bank_transaction_id' => $bankTx->id,
                    'bank_transaction' => $bankTx,
                    'gl_transaction_id' => $match->id,
                    'gl_transaction' => $match,
                    'match_confidence' => $this->calculateConfidence($bankTx, $match),
                    'match_type' => $this->getMatchType($bankTx, $match),
                ]);

                // Remove matched GL transaction from pool
                $unmatchedGL = $unmatchedGL->reject(fn($gl) => $gl->id === $match->id);
            } else {
                $unmatched->push($bankTx);
            }
        }

        return [
            'matches' => $matches,
            'unmatched_bank' => $unmatched,
            'unmatched_gl' => $unmatchedGL,
            'match_rate' => $bankTransactions->count() > 0
                ? round(($matches->count() / $bankTransactions->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get bank transactions for reconciliation
     *
     * @param BankAccount $account
     * @param Carbon $date
     * @return Collection
     */
    private function getBankTransactions(BankAccount $account, Carbon $date): Collection
    {
        return BankTransaction::where('bank_account_id', $account->id)
            ->whereDate('transaction_date', '<=', $date->toDateString())
            ->where('reconciliation_status', 'unreconciled')
            ->where('is_active', true)
            ->orderBy('transaction_date')
            ->get();
    }

    /**
     * Get GL transactions for bank account
     *
     * @param BankAccount $account
     * @param Carbon $date
     * @return Collection
     */
    private function getGLTransactions(BankAccount $account, Carbon $date): Collection
    {
        // Find the GL account for this bank account
        $glAccount = Account::find($account->gl_account_id);

        if (!$glAccount) {
            return collect();
        }

        // Get journal lines for this account
        return JournalLine::where('account_id', $glAccount->id)
            ->whereHas('journalEntry', function ($query) use ($date) {
                $query->where('status', 'posted')
                    ->whereDate('accounting_date', '<=', $date->toDateString());
            })
            ->with('journalEntry')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find matching GL transaction for bank transaction
     *
     * @param BankTransaction $bankTx
     * @param Collection $glTransactions
     * @return JournalLine|null
     */
    private function findMatch(BankTransaction $bankTx, Collection $glTransactions): ?JournalLine
    {
        // Strategy 1: Exact amount and date match
        $exactMatch = $glTransactions->first(function ($gl) use ($bankTx) {
            $glAmount = $this->getGLAmount($gl);
            $bankAmount = $bankTx->amount;

            return abs($glAmount - $bankAmount) < 0.01
                && Carbon::parse($gl->journalEntry->accounting_date)->isSameDay($bankTx->transaction_date);
        });

        if ($exactMatch) {
            return $exactMatch;
        }

        // Strategy 2: Exact amount, date within ±3 days
        $dateRangeMatch = $glTransactions->first(function ($gl) use ($bankTx) {
            $glAmount = $this->getGLAmount($gl);
            $bankAmount = $bankTx->amount;

            $dateDiff = abs(
                Carbon::parse($gl->journalEntry->accounting_date)
                    ->diffInDays(Carbon::parse($bankTx->transaction_date))
            );

            return abs($glAmount - $bankAmount) < 0.01 && $dateDiff <= 3;
        });

        if ($dateRangeMatch) {
            return $dateRangeMatch;
        }

        // Strategy 3: Reference number match
        $referenceMatch = $glTransactions->first(function ($gl) use ($bankTx) {
            $reference = $gl->journalEntry->reference ?? '';
            $bankReference = $bankTx->reference ?? '';

            return !empty($reference)
                && !empty($bankReference)
                && str_contains(strtolower($reference), strtolower($bankReference));
        });

        if ($referenceMatch) {
            return $referenceMatch;
        }

        return null;
    }

    /**
     * Get amount from GL transaction (debit or credit depending on transaction type)
     *
     * @param JournalLine $gl
     * @return float
     */
    private function getGLAmount(JournalLine $gl): float
    {
        // For bank accounts, debits are increases, credits are decreases
        return $gl->debit_amount - $gl->credit_amount;
    }

    /**
     * Calculate match confidence score
     *
     * @param BankTransaction $bankTx
     * @param JournalLine $gl
     * @return float
     */
    private function calculateConfidence(BankTransaction $bankTx, JournalLine $gl): float
    {
        $score = 0;

        // Amount match: 40 points
        $glAmount = $this->getGLAmount($gl);
        $bankAmount = $bankTx->amount;

        if (abs($glAmount - $bankAmount) < 0.01) {
            $score += 40;
        }

        // Date match: 30 points (exact), 20 points (±1 day), 10 points (±3 days)
        $dateDiff = abs(
            Carbon::parse($gl->journalEntry->accounting_date)
                ->diffInDays(Carbon::parse($bankTx->transaction_date))
        );

        if ($dateDiff === 0) {
            $score += 30;
        } elseif ($dateDiff === 1) {
            $score += 20;
        } elseif ($dateDiff <= 3) {
            $score += 10;
        }

        // Reference match: 20 points
        $reference = $gl->journalEntry->reference ?? '';
        $bankReference = $bankTx->reference ?? '';

        if (!empty($reference) && !empty($bankReference)) {
            if (str_contains(strtolower($reference), strtolower($bankReference))) {
                $score += 20;
            }
        }

        // Description similarity: 10 points
        $description = $gl->description ?? '';
        $bankDescription = $bankTx->description ?? '';

        if (!empty($description) && !empty($bankDescription)) {
            similar_text(
                strtolower($description),
                strtolower($bankDescription),
                $similarity
            );

            if ($similarity > 50) {
                $score += 10;
            }
        }

        return $score;
    }

    /**
     * Get match type description
     *
     * @param BankTransaction $bankTx
     * @param JournalLine $gl
     * @return string
     */
    private function getMatchType(BankTransaction $bankTx, JournalLine $gl): string
    {
        $glAmount = $this->getGLAmount($gl);
        $bankAmount = $bankTx->amount;

        $dateDiff = abs(
            Carbon::parse($gl->journalEntry->accounting_date)
                ->diffInDays(Carbon::parse($bankTx->transaction_date))
        );

        // Exact match
        if (abs($glAmount - $bankAmount) < 0.01 && $dateDiff === 0) {
            return 'exact_match';
        }

        // Date variance
        if (abs($glAmount - $bankAmount) < 0.01 && $dateDiff <= 3) {
            return 'date_variance';
        }

        // Reference match
        $reference = $gl->journalEntry->reference ?? '';
        $bankReference = $bankTx->reference ?? '';

        if (!empty($reference) && !empty($bankReference) &&
            str_contains(strtolower($reference), strtolower($bankReference))) {
            return 'reference_match';
        }

        return 'fuzzy_match';
    }

    /**
     * Mark bank transaction as reconciled
     *
     * @param BankTransaction $bankTx
     * @param JournalLine $gl
     * @param int $userId
     * @return bool
     */
    public function markAsReconciled(BankTransaction $bankTx, JournalLine $gl, int $userId): bool
    {
        return DB::transaction(function () use ($bankTx, $gl, $userId) {
            $bankTx->update([
                'reconciliation_status' => 'reconciled',
                'reconciled_at' => now(),
                'reconciled_by_id' => $userId,
                'journal_line_id' => $gl->id,
            ]);

            // Update GL line metadata
            $metadata = $gl->metadata ?? [];
            $metadata['bank_reconciliation'] = [
                'bank_transaction_id' => $bankTx->id,
                'reconciled_at' => now()->toDateTimeString(),
                'reconciled_by_id' => $userId,
            ];

            $gl->update(['metadata' => $metadata]);

            return true;
        });
    }

    /**
     * Bulk reconcile matched transactions
     *
     * @param array $matches
     * @param int $userId
     * @return array
     */
    public function bulkReconcile(array $matches, int $userId): array
    {
        $reconciled = 0;
        $failed = 0;

        foreach ($matches as $match) {
            try {
                $bankTx = BankTransaction::find($match['bank_transaction_id']);
                $gl = JournalLine::find($match['gl_transaction_id']);

                if ($bankTx && $gl) {
                    $this->markAsReconciled($bankTx, $gl, $userId);
                    $reconciled++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return [
            'reconciled' => $reconciled,
            'failed' => $failed,
        ];
    }

    /**
     * Get reconciliation summary for bank account
     *
     * @param BankAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getReconciliationSummary(
        BankAccount $account,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $transactions = BankTransaction::where('bank_account_id', $account->id)
            ->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_active', true)
            ->get();

        $reconciled = $transactions->where('reconciliation_status', 'reconciled');
        $unreconciled = $transactions->where('reconciliation_status', 'unreconciled');

        $reconciledBalance = $reconciled->sum('amount');
        $unreconciledBalance = $unreconciled->sum('amount');

        return [
            'total_transactions' => $transactions->count(),
            'reconciled_count' => $reconciled->count(),
            'unreconciled_count' => $unreconciled->count(),
            'reconciled_balance' => $reconciledBalance,
            'unreconciled_balance' => $unreconciledBalance,
            'reconciliation_rate' => $transactions->count() > 0
                ? round(($reconciled->count() / $transactions->count()) * 100, 2)
                : 0,
        ];
    }
}

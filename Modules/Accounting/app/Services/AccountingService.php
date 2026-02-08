<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Exception;

class AccountingService
{
    protected SequenceService $sequenceService;

    public function __construct(SequenceService $sequenceService)
    {
        $this->sequenceService = $sequenceService;
    }

    /**
     * Create and post a journal entry with lines
     *
     * @param string $journalCode Journal code (AR, AP, GL, etc.)
     * @param string $entryDate Entry date (Y-m-d format)
     * @param string $description Entry description
     * @param string|null $reference External reference
     * @param array $lines Array of lines with account_id, debit_amount, credit_amount, description
     * @return JournalEntry
     * @throws Exception
     */
    public function createJournalEntry(
        string $journalCode,
        string $entryDate,
        string $description,
        ?string $reference,
        array $lines
    ): JournalEntry {
        return DB::transaction(function () use ($journalCode, $entryDate, $description, $reference, $lines) {
            // Find journal by code
            $journal = Journal::where('code', $journalCode)->where('status', 'active')->first();

            if (!$journal) {
                throw new Exception("Journal with code '{$journalCode}' not found or not active");
            }

            // Find fiscal period for this date
            $fiscalPeriod = FiscalPeriod::where('start_date', '<=', $entryDate)
                ->where('end_date', '>=', $entryDate)
                ->where('status', 'open')
                ->first();

            if (!$fiscalPeriod) {
                throw new Exception("No open fiscal period found for date {$entryDate}");
            }

            // Create journal entry
            $entry = JournalEntry::create([
                'fiscal_period_id' => $fiscalPeriod->id,
                'journal_id' => $journal->id,
                'date' => $entryDate,
                'description' => $description,
                'reference' => $reference,
                'status' => JournalEntry::STATUS_DRAFT,
                'total_debit' => 0,
                'total_credit' => 0,
            ]);

            // Create journal lines
            foreach ($lines as $lineData) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $lineData['account_id'],
                    'debit' => $lineData['debit_amount'] ?? 0,
                    'credit' => $lineData['credit_amount'] ?? 0,
                    'description' => $lineData['description'] ?? $description,
                ]);
            }

            // Reload with lines
            $entry->refresh();

            // Post the entry
            $this->postJournalEntry($entry);

            return $entry->fresh(['journalLines']);
        });
    }

    /**
     * Post a journal entry with full validation and audit trail
     *
     * @param JournalEntry $entry
     * @return bool
     * @throws Exception
     */
    public function postJournalEntry(JournalEntry $entry): bool
    {
        return DB::transaction(function () use ($entry) {
            // Idempotency check
            if ($entry->status === JournalEntry::STATUS_POSTED) {
                return true;
            }

            // Critical business validations
            $this->validateBalance($entry);
            $this->validatePeriod($entry);
            $this->validateAccounts($entry);

            // Assign sequence if not already assigned
            if (!$entry->number) {
                $entry->number = $this->sequenceService->getNextSequence(
                    $entry->journal,
                    $entry->date
                );
            }

            // Post with audit trail
            $entry->update([
                'status' => JournalEntry::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by_id' => auth()->id()
            ]);

            return true;
        });
    }

    /**
     * Validate that journal entry is balanced
     *
     * @param JournalEntry $entry
     * @throws Exception
     */
    protected function validateBalance(JournalEntry $entry): void
    {
        $totalDebit = $entry->journalLines()->sum('debit');
        $totalCredit = $entry->journalLines()->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception(
                "Journal entry is not balanced. Debit: {$totalDebit}, Credit: {$totalCredit}"
            );
        }

        // Update totals
        $entry->total_debit = $totalDebit;
        $entry->total_credit = $totalCredit;
        $entry->save();
    }

    /**
     * Validate that fiscal period is open
     *
     * @param JournalEntry $entry
     * @throws Exception
     */
    protected function validatePeriod(JournalEntry $entry): void
    {
        $period = $entry->fiscalPeriod;

        if (!$period) {
            throw new Exception('Journal entry must have a fiscal period assigned');
        }

        if ($period->status !== 'open') {
            throw new Exception(
                "Cannot post to closed fiscal period: {$period->name}"
            );
        }
    }

    /**
     * Validate that all accounts are postable
     *
     * @param JournalEntry $entry
     * @throws Exception
     */
    protected function validateAccounts(JournalEntry $entry): void
    {
        $lines = $entry->journalLines()->with('account')->get();

        foreach ($lines as $line) {
            if (!$line->account) {
                throw new Exception("Journal line {$line->id} has no account assigned");
            }

            if (!$line->account->is_postable) {
                throw new Exception(
                    "Account {$line->account->code} - {$line->account->name} is not postable"
                );
            }

            if ($line->account->status !== 'active') {
                throw new Exception(
                    "Account {$line->account->code} - {$line->account->name} is not active"
                );
            }
        }
    }

    /**
     * Reverse a posted journal entry
     *
     * @param JournalEntry $entry
     * @param string|null $reason
     * @return JournalEntry The reversal entry
     * @throws Exception
     */
    public function reverseJournalEntry(JournalEntry $entry, ?string $reason = null): JournalEntry
    {
        return DB::transaction(function () use ($entry, $reason) {
            // Validate original entry is posted
            if ($entry->status !== JournalEntry::STATUS_POSTED) {
                throw new Exception('Only posted entries can be reversed');
            }

            // Validate period is open
            $this->validatePeriod($entry);

            // Create reversal entry
            $reversalEntry = $entry->replicate(['number', 'posted_at', 'posted_by_id']);
            $reversalEntry->description = "REVERSAL: {$entry->description}";
            $reversalEntry->status = JournalEntry::STATUS_DRAFT;
            $reversalEntry->reversal_of_id = $entry->id;
            $reversalEntry->reversal_reason = $reason;
            $reversalEntry->save();

            // Copy and reverse lines
            foreach ($entry->journalLines as $line) {
                $reversalLine = $line->replicate();
                $reversalLine->journal_entry_id = $reversalEntry->id;
                // Swap debit and credit
                $reversalLine->debit = $line->credit;
                $reversalLine->credit = $line->debit;
                $reversalLine->save();
            }

            // Post the reversal
            $this->postJournalEntry($reversalEntry);

            return $reversalEntry;
        });
    }

    /**
     * Approve a draft journal entry
     *
     * @param JournalEntry $entry
     * @return bool
     * @throws Exception
     */
    public function approveJournalEntry(JournalEntry $entry): bool
    {
        return DB::transaction(function () use ($entry) {
            if ($entry->status !== JournalEntry::STATUS_DRAFT) {
                throw new Exception('Only draft entries can be approved');
            }

            // Validate balance before approval
            $this->validateBalance($entry);

            $entry->update([
                'status' => JournalEntry::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by_id' => auth()->id()
            ]);

            return true;
        });
    }
}

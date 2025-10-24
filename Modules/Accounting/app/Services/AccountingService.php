<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Account;
use Exception;

class AccountingService
{
    protected SequenceService $sequenceService;

    public function __construct(SequenceService $sequenceService)
    {
        $this->sequenceService = $sequenceService;
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
            if ($entry->status === 'posted') {
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
                'status' => 'posted',
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

        if (bccomp((string)$totalDebit, (string)$totalCredit, 2) !== 0) {
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
            if ($entry->status !== 'posted') {
                throw new Exception('Only posted entries can be reversed');
            }

            // Validate period is open
            $this->validatePeriod($entry);

            // Create reversal entry
            $reversalEntry = $entry->replicate(['number', 'posted_at', 'posted_by_id']);
            $reversalEntry->description = "REVERSAL: {$entry->description}";
            $reversalEntry->status = 'draft';
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
        if ($entry->status !== 'draft') {
            throw new Exception('Only draft entries can be approved');
        }

        // Validate balance before approval
        $this->validateBalance($entry);

        $entry->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_id' => auth()->id()
        ]);

        return true;
    }
}

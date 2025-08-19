<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\JournalEntry;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    /**
     * Post a journal entry after validation
     */
    public function post(JournalEntry $entry): bool
    {
        if (!$entry->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft entries can be posted.'
            ]);
        }

        $this->validateForPosting($entry);

        return DB::transaction(function () use ($entry) {
            $entry->status = JournalEntry::STATUS_POSTED;
            $entry->posted_at = now();
            $entry->posted_by_id = auth()->id();
            
            return $entry->save();
        });
    }

    /**
     * Validate entry for posting
     */
    protected function validateForPosting(JournalEntry $entry): void
    {
        // Validar que tenga líneas
        if ($entry->journalLines()->count() === 0) {
            throw ValidationException::withMessages([
                'lines' => 'Journal entry must have at least one line.'
            ]);
        }

        // Validar balance cero (regla dura F1)
        if (!$entry->isBalanced()) {
            throw ValidationException::withMessages([
                'balance' => 'Journal entry must be balanced (total debits = total credits).'
            ]);
        }

        // Validar cuentas postables (regla dura F1)
        if (!$entry->hasPostableAccounts()) {
            throw ValidationException::withMessages([
                'accounts' => 'All accounts must be postable (is_postable = true).'
            ]);
        }

        // TODO F2: Validar periodo abierto (opcional F1)
        // $this->validateOpenPeriod($entry);
    }

    /**
     * Create a simple journal entry with lines
     */
    public function createWithLines(array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($data, $lines) {
            $entry = JournalEntry::create([
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'],
                'journal_id' => $data['journal_id'] ?? null,
                'period_id' => $data['period_id'] ?? null,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
            ]);

            foreach ($lines as $line) {
                $entry->journalLines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Create and immediately post journal entry
     */
    public function createAndPost(array $data, array $lines): JournalEntry
    {
        $entry = $this->createWithLines($data, $lines);
        $this->post($entry);
        
        return $entry->refresh();
    }

    /**
     * Get entry totals for validation
     */
    public function getTotals(JournalEntry $entry): array
    {
        return [
            'total_debit' => $entry->getTotalDebit(),
            'total_credit' => $entry->getTotalCredit(),
            'difference' => $entry->getTotalDebit() - $entry->getTotalCredit(),
            'is_balanced' => $entry->isBalanced(),
        ];
    }
}
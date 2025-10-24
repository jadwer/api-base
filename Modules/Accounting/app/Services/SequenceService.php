<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalSequence;
use Carbon\Carbon;

class SequenceService
{
    /**
     * Get next sequence number for a journal with fiscal year support
     *
     * @param Journal $journal
     * @param Carbon $date
     * @return string Formatted sequence: {prefix}-{YYYY}-{MM}-{#####}
     */
    public function getNextSequence(Journal $journal, Carbon $date): string
    {
        return DB::transaction(function () use ($journal, $date) {
            // First-or-create atómico para evitar race conditions
            $sequence = JournalSequence::firstOrCreate(
                [
                    'company_id' => $journal->company_id,
                    'journal_id' => $journal->id,
                    'fiscal_year' => $date->year
                ],
                [
                    'current_number' => 0,
                    'prefix' => $journal->prefix,
                    'created_by_id' => auth()->id()
                ]
            );

            // Lock específico y increment atómico (PostgreSQL compliant)
            $sequence = JournalSequence::where('id', $sequence->id)
                ->lockForUpdate()
                ->first();

            $sequence->increment('current_number');

            // Formato unificado: {prefix}-{YYYY}-{MM}-{#####}
            // Use journal prefix directly to avoid null prefix issues
            return sprintf('%s-%04d-%02d-%05d',
                $journal->prefix ?? $sequence->prefix,
                $date->year,
                $date->month,
                $sequence->current_number
            );
        });
    }

    /**
     * Reset sequence for a journal and fiscal year
     *
     * @param Journal $journal
     * @param int $fiscalYear
     * @return bool
     */
    public function resetSequence(Journal $journal, int $fiscalYear): bool
    {
        return DB::transaction(function () use ($journal, $fiscalYear) {
            $sequence = JournalSequence::where('company_id', $journal->company_id)
                ->where('journal_id', $journal->id)
                ->where('fiscal_year', $fiscalYear)
                ->lockForUpdate()
                ->first();

            if ($sequence) {
                $sequence->update(['current_number' => 0]);
                return true;
            }

            return false;
        });
    }

    /**
     * Get current sequence number without incrementing
     *
     * @param Journal $journal
     * @param int $fiscalYear
     * @return int
     */
    public function getCurrentNumber(Journal $journal, int $fiscalYear): int
    {
        $sequence = JournalSequence::where('company_id', $journal->company_id)
            ->where('journal_id', $journal->id)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        return $sequence ? $sequence->current_number : 0;
    }

    /**
     * Preview next sequence number format without saving
     *
     * @param Journal $journal
     * @param Carbon $date
     * @return string
     */
    public function previewNextSequence(Journal $journal, Carbon $date): string
    {
        $currentNumber = $this->getCurrentNumber($journal, $date->year);
        $nextNumber = $currentNumber + 1;

        return sprintf('%s-%04d-%02d-%05d',
            $journal->prefix,
            $date->year,
            $date->month,
            $nextNumber
        );
    }
}

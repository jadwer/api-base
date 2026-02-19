<?php

namespace Modules\Accounting\JsonApi\V1\JournalEntries;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryRequest extends ResourceRequest
{
    public function rules(): array
    {
        $journalentry = $this->model();
        $isUpdate = $journalentry && $journalentry->exists;


        return [
            'journalId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'fiscalPeriodId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'number' => ['nullable', 'string', 'max:255', Rule::unique('journal_entries')->ignore($journalentry?->id)],
            'date' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'totalDebit' => ['nullable', 'numeric'],
            'totalCredit' => ['nullable', 'numeric'],
            'status' => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(JournalEntry::STATUSES)],
            'approvedAt' => ['nullable', 'string'],
            'approvedById' => ['nullable', 'integer'],
            'postedAt' => ['nullable', 'string'],
            'postedById' => ['nullable', 'integer'],
            'reversal_of_id' => [
                'nullable',
                'integer',
                'exists:journal_entries,id',
                function ($attribute, $value, $fail) use ($journalentry) {
                    if ($value && $this->wouldCreateCircularReference($journalentry, $value)) {
                        $fail('No se puede crear una referencia circular en las reversiones de asientos contables.');
                    }
                }
            ],
            'reversal_reason' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Check if setting reversal_of_id would create a circular reference
     *
     * AC-007: Prevent circular references in journal reversals
     *
     * @param JournalEntry|null $currentEntry
     * @param int $proposedReversalId
     * @return bool
     */
    protected function wouldCreateCircularReference(?JournalEntry $currentEntry, int $proposedReversalId): bool
    {
        // If creating a new entry, just check if proposed reversal exists
        if (!$currentEntry || !$currentEntry->exists) {
            return false;
        }

        // Cannot reverse itself
        if ($currentEntry->id === $proposedReversalId) {
            return true;
        }

        // Check if the proposed reversal is already a reversal of the current entry
        // This would create a direct circular reference: A -> B and B -> A
        $proposedEntry = JournalEntry::find($proposedReversalId);
        if ($proposedEntry && $proposedEntry->reversal_of_id === $currentEntry->id) {
            return true;
        }

        // Check if the proposed reversal is a descendant of the current entry
        // This prevents chains like: A -> B -> C, then trying to set C -> A
        $visited = [];
        $checkId = $proposedReversalId;

        while ($checkId !== null) {
            // Prevent infinite loops in case there are existing circular references
            if (in_array($checkId, $visited)) {
                return true;
            }

            $visited[] = $checkId;

            // If we reach the current entry, it's circular
            if ($checkId === $currentEntry->id) {
                return true;
            }

            // Move up the chain
            $entry = JournalEntry::find($checkId);
            $checkId = $entry?->reversal_of_id;
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'journalId.required' => 'El campo Journal id es obligatorio.',
            'fiscalPeriodId.required' => 'El campo Fiscal period id es obligatorio.',
            'number.string' => 'El campo Number debe ser texto.',
            'number.max' => 'El campo Number no puede tener más de 255 caracteres.',
            'number.unique' => 'Este Number ya está en uso.',
            'date.required' => 'El campo Date es obligatorio.',
            'date.date' => 'El campo Date debe ser una fecha válida.',
            'reference.string' => 'El campo Reference debe ser texto.',
            'reference.max' => 'El campo Reference no puede tener más de 255 caracteres.',
            'description.string' => 'El campo Description debe ser texto.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.in' => 'El campo Status debe ser: draft, approved, posted o reversed.',
            'reversal_reason.string' => 'El campo Reversal reason debe ser texto.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}

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
            // En update el Schema ignora status (readOnlyOnUpdate); la regla queda
            // suave para no romper con 422 a clientes que aun lo envian.
            // Al crear solo se acepta borrador: antes un POST con status=posted
            // nacia contabilizada sin cuadre, periodo ni folio. Se contabiliza
            // por POST journal-entries/{id}/post (AccountingService).
            'status' => $isUpdate
                ? ['sometimes', 'string']
                : ['sometimes', 'string', Rule::in([JournalEntry::STATUS_DRAFT])],
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
            'journalId.required' => 'El campo diario es obligatorio.',
            'fiscalPeriodId.required' => 'El campo periodo fiscal es obligatorio.',
            'number.string' => 'El campo número debe ser texto.',
            'number.max' => 'El campo número no puede tener más de 255 caracteres.',
            'number.unique' => 'Este folio ya está en uso.',
            'date.required' => 'El campo fecha es obligatorio.',
            'date.date' => 'El campo fecha debe ser una fecha válida.',
            'reference.string' => 'El campo referencia debe ser texto.',
            'reference.max' => 'El campo referencia no puede tener más de 255 caracteres.',
            'description.string' => 'El campo descripción debe ser texto.',
            'status.required' => 'El campo estado es obligatorio.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.in' => 'La póliza se crea en borrador; para contabilizarla usa la acción Contabilizar.',
            'reversal_reason.string' => 'El campo motivo de la reversa debe ser texto.',
            'metadata.array' => 'El campo metadatos debe ser un arreglo.',
        ];
    }
}

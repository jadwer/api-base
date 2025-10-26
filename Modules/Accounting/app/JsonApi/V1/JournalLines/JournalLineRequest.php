<?php

namespace Modules\Accounting\JsonApi\V1\JournalLines;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class JournalLineRequest extends ResourceRequest
{
    public function rules(): array
    {
        $journalline = $this->model();
        $isUpdate = $journalline && $journalline->exists;

        
        return [
            'journalEntryId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'accountId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'contact_id' => ['nullable', 'string'],
            'debit' => [$isUpdate ? 'sometimes' : 'required', 'numeric'],
            'credit' => [$isUpdate ? 'sometimes' : 'required', 'numeric'],
            'description' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'journalEntryId.required' => 'El campo Journal entry id es obligatorio.',
            'accountId.required' => 'El campo Account id es obligatorio.',
            'debit.required' => 'El campo Debit es obligatorio.',
            'credit.required' => 'El campo Credit es obligatorio.',
            'description.string' => 'El campo Description debe ser texto.',
            'reference.string' => 'El campo Reference debe ser texto.',
            'reference.max' => 'El campo Reference no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}

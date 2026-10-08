<?php

namespace Modules\Contacts\JsonApi\V1\ContactDocuments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;
use Modules\Contacts\Support\ContactDocumentTypes;

class ContactDocumentRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'contactId' => [$isCreating ? 'required' : 'sometimes', 'integer'],
            'documentType' => ['nullable', 'string', 'max:255', Rule::in(ContactDocumentTypes::ALL)],
            'filePath' => ['nullable', 'string', 'max:255'],
            'originalFilename' => ['nullable', 'string', 'max:255'],
            'mimeType' => ['nullable', 'string', 'max:255'],
            'fileSize' => ['nullable', 'integer'],
            'uploadedBy' => ['nullable', 'integer'],
            'verifiedAt' => ['nullable', 'date'],
            'verifiedBy' => ['nullable', 'integer'],
            'expiresAt' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'contactId.integer' => 'El campo Contact id debe ser un número entero.',
            'documentType.string' => 'El campo Document type debe ser texto.',
            'documentType.max' => 'El campo Document type no puede tener más de 255 caracteres.',
            'filePath.string' => 'El campo File path debe ser texto.',
            'filePath.max' => 'El campo File path no puede tener más de 255 caracteres.',
            'originalFilename.string' => 'El campo Original filename debe ser texto.',
            'originalFilename.max' => 'El campo Original filename no puede tener más de 255 caracteres.',
            'mimeType.string' => 'El campo Mime type debe ser texto.',
            'mimeType.max' => 'El campo Mime type no puede tener más de 255 caracteres.',
            'fileSize.integer' => 'El campo File size debe ser un número entero.',
            'uploadedBy.integer' => 'El campo Uploaded by debe ser un número entero.',
            'verifiedAt.date' => 'El campo Verified at debe ser una fecha válida.',
            'verifiedBy.integer' => 'El campo Verified by debe ser un número entero.',
            'expiresAt.date' => 'El campo Expires at debe ser una fecha válida.',
            'notes.string' => 'El campo Notes debe ser texto.',
        ];
    }
}

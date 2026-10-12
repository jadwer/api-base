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
            'contactId.integer' => 'El campo contacto debe ser un número entero.',
            'documentType.string' => 'El campo tipo de documento debe ser texto.',
            'documentType.max' => 'El campo tipo de documento no puede tener más de 255 caracteres.',
            'filePath.string' => 'El campo ruta del archivo debe ser texto.',
            'filePath.max' => 'El campo ruta del archivo no puede tener más de 255 caracteres.',
            'originalFilename.string' => 'El campo nombre del archivo debe ser texto.',
            'originalFilename.max' => 'El campo nombre del archivo no puede tener más de 255 caracteres.',
            'mimeType.string' => 'El campo tipo de archivo debe ser texto.',
            'mimeType.max' => 'El campo tipo de archivo no puede tener más de 255 caracteres.',
            'fileSize.integer' => 'El campo tamaño del archivo debe ser un número entero.',
            'uploadedBy.integer' => 'El campo subido por debe ser un número entero.',
            'verifiedAt.date' => 'El campo fecha de verificación debe ser una fecha válida.',
            'verifiedBy.integer' => 'El campo verificado por debe ser un número entero.',
            'expiresAt.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
            'notes.string' => 'El campo notas debe ser texto.',
        ];
    }
}

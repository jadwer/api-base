<?php

namespace Modules\Contacts\JsonApi\V1\ContactDocuments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ContactDocumentRequest extends ResourceRequest
{
    public function rules(): array
    {
        $contactdocument = $this->model();
        
        return [
            'contact_id' => ['nullable', 'integer'],
            'document_type' => ['nullable', 'string', 'max:255', Rule::in(['rfc', 'cedula_fiscal', 'ine', 'constancia_sat', 'opinion_sat', 'certificado_sello', 'comprobante_domicilio', 'cotizacion', 'orden_compra', 'factura', 'contrato', 'otros'])],
            'file_path' => ['nullable', 'string', 'max:255'],
            'original_filename' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer'],
            'uploaded_by' => ['nullable', 'integer'],
            'verified_at' => ['nullable', 'date'],
            'verified_by' => ['nullable', 'integer'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.integer' => 'El campo Contact id debe ser un número entero.',
            'document_type.string' => 'El campo Document type debe ser texto.',
            'document_type.max' => 'El campo Document type no puede tener más de 255 caracteres.',
            'file_path.string' => 'El campo File path debe ser texto.',
            'file_path.max' => 'El campo File path no puede tener más de 255 caracteres.',
            'original_filename.string' => 'El campo Original filename debe ser texto.',
            'original_filename.max' => 'El campo Original filename no puede tener más de 255 caracteres.',
            'mime_type.string' => 'El campo Mime type debe ser texto.',
            'mime_type.max' => 'El campo Mime type no puede tener más de 255 caracteres.',
            'file_size.integer' => 'El campo File size debe ser un número entero.',
            'uploaded_by.integer' => 'El campo Uploaded by debe ser un número entero.',
            'verified_at.date' => 'El campo Verified at debe ser una fecha válida.',
            'verified_by.integer' => 'El campo Verified by debe ser un número entero.',
            'expires_at.date' => 'El campo Expires at debe ser una fecha válida.',
            'notes.string' => 'El campo Notes debe ser texto.',
        ];
    }
}

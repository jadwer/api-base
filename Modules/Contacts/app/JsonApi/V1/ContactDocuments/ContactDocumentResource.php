<?php

namespace Modules\Contacts\JsonApi\V1\ContactDocuments;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ContactDocumentResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'contactId' => $this->contact_id,
            'documentType' => $this->document_type,
            'filePath' => $this->file_path,
            'originalFilename' => $this->original_filename,
            'mimeType' => $this->mime_type,
            'fileSize' => $this->file_size,
            'uploadedBy' => $this->uploaded_by,
            'verifiedAt' => $this->verified_at,
            'verifiedBy' => $this->verified_by,
            'expiresAt' => $this->expires_at,
            'notes' => $this->notes,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'contact' => $this->relation('contact'),
            'contacts' => $this->relation('contacts'),
        ];
    }
}

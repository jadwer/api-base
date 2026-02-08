<?php

namespace Modules\Contacts\JsonApi\V1\ContactDocuments;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Contacts\Models\ContactDocument;

class ContactDocumentSchema extends Schema
{
    public static string $model = ContactDocument::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('contactId', 'contact_id')->sortable(),
            Str::make('documentType', 'document_type')->sortable(),
            Str::make('filePath', 'file_path'),
            Str::make('originalFilename', 'original_filename'),
            Str::make('mimeType', 'mime_type'),
            Number::make('fileSize', 'file_size'),
            Number::make('uploadedBy', 'uploaded_by'),
            DateTime::make('verifiedAt', 'verified_at'),
            Number::make('verifiedBy', 'verified_by'),
            DateTime::make('expiresAt', 'expires_at'),
            Str::make('notes'),
            ArrayHash::make('metadata'),
            
            // Relationships
            BelongsTo::make('contact')->type('contacts'),
            
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('contactId', 'contact_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('document_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('file_path'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('original_filename'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('mime_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('file_size'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('uploaded_by'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('verified_by'),
        ];
    }


    public function includePaths(): array
    {
        return [
            'contact',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "contact-documents";
    }
}
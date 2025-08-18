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
            
            Number::make('contactId')->sortable(),
            Str::make('documentType')->sortable(),
            Str::make('filePath')->sortable(),
            Str::make('originalFilename')->sortable(),
            Str::make('mimeType')->sortable(),
            Number::make('fileSize')->sortable(),
            Number::make('uploadedBy')->sortable(),
            DateTime::make('verifiedAt')->sortable(),
            Number::make('verifiedBy')->sortable(),
            DateTime::make('expiresAt')->sortable(),
            Str::make('notes'),
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('contact'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('contact_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('document_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('file_path'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('original_filename'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('mime_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('file_size'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('uploaded_by'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('verified_by'),
        ];
    }

    public function sortables(): array
    {
        return [
            'contact_id',
            'document_type',
            'file_path',
            'original_filename',
            'mime_type',
            'file_size',
            'uploaded_by',
            'verified_at',
            'verified_by',
            'expires_at',
            'created_at',
            'updated_at',
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
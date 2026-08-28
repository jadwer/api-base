<?php

namespace Modules\Billing\JsonApi\V1\DocumentLegends;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Billing\Models\DocumentLegend;

class DocumentLegendSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = DocumentLegend::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('documentType', 'document_type'),
            Str::make('body'),
            Boolean::make('isActive', 'is_active'),

            DateTime::make('createdAt')->readOnly(),
            DateTime::make('updatedAt')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('documentType', 'document_type'),
            Where::make('isActive', 'is_active')->asBoolean(),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage(25);
    }
}

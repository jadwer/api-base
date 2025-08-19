<?php

namespace Modules\Finance\JsonApi\V1\APInvoiceLines;

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
use Modules\Finance\Models\APInvoiceLine;

class APInvoiceLineSchema extends Schema
{
    public static string $model = APInvoiceLine::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('apInvoiceId'),
            Str::make('description')->sortable(),
            Number::make('quantity')->sortable(),
            Number::make('unitPrice')->sortable(),
            Number::make('discount')->sortable(),
            Number::make('lineTotal')->sortable(),
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('description'),
        ];
    }

    public function sortables(): array
    {
        return [
            'description',
            'quantity',
            'unit_price',
            'discount',
            'line_total',
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [

        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "a-p-invoice-lines";
    }
}
<?php

namespace Modules\Accounting\JsonApi\V1\FiscalPeriods;

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
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodSchema extends Schema
{
    public static string $model = FiscalPeriod::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Str::make('name')->sortable(),
            Number::make('year')->sortable(),
            Number::make('month')->sortable(),
            DateTime::make('startDate')->sortable(),
            DateTime::make('endDate')->sortable(),
            Str::make('status')->sortable(),
            DateTime::make('closedAt')->sortable(),
            Number::make('closedById'),
            Number::make('closingEntryId'),
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            HasMany::make('journalEntries'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('year'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('month'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
        ];
    }

    public function sortables(): array
    {
        return [
            'name',
            'year',
            'month',
            'start_date',
            'end_date',
            'status',
            'closed_at',
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [
            'journalEntries',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "fiscal-periods";
    }
}
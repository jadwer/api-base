<?php

namespace Modules\Accounting\JsonApi\V1\JournalEntries;

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
use Modules\Accounting\Models\JournalEntry;

class JournalEntrySchema extends Schema
{
    public static string $model = JournalEntry::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('journalId'),
            Number::make('periodId'),
            Str::make('number')->sortable(),
            DateTime::make('date')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('exchangeRate')->sortable(),
            Str::make('reference')->sortable(),
            Str::make('description'),
            Str::make('status')->sortable(),
            Number::make('approvedById'),
            Number::make('postedById'),
            DateTime::make('postedAt')->sortable(),
            Number::make('reversalOfId'),
            Str::make('sourceType')->sortable(),
            Number::make('sourceId')->sortable(),
            // Metadata
            ArrayHash::make('metadata'),
            
            // Relationships
            HasMany::make('journalLines')->type('journal-lines'),
            BelongsTo::make('journal')->type('journals'),
            BelongsTo::make('fiscalPeriod')->type('fiscal-periods'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('reference'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('source_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('source_id'),
        ];
    }


    public function includePaths(): array
    {
        return [
            'journalLines',
            'journalLines.account',
            'journal',
            'fiscalPeriod',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "journal-entries";
    }
}
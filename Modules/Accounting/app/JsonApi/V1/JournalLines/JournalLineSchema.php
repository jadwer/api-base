<?php

namespace Modules\Accounting\JsonApi\V1\JournalLines;

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
use Modules\Accounting\Models\JournalLine;

class JournalLineSchema extends Schema
{
    public static string $model = JournalLine::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('journalEntryId'),
            Number::make('accountId'),
            Number::make('debit')->sortable(),
            Number::make('credit')->sortable(),
            Number::make('baseAmount')->sortable(),
            Number::make('costCenterId'),
            Number::make('partnerId'),
            Str::make('memo')->sortable(),
            
            // Relationships
            BelongsTo::make('account')->type('accounts'),
            BelongsTo::make('journalEntry')->type('journal-entries'),
            
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('memo'),
        ];
    }

    public function sortables(): array
    {
        return [
            'debit',
            'credit',
            'base_amount',
            'memo',
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [
            'account',
            'journalEntry',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "journal-lines";
    }
}
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
            
            Number::make('journalId', 'journal_id'),
            Number::make('fiscalPeriodId', 'fiscal_period_id'),
            Str::make('number')->sortable(),
            DateTime::make('date')->sortable(),
            Str::make('reference')->sortable(),
            Str::make('description'),
            Number::make('totalDebit', 'total_debit')->sortable(),
            Number::make('totalCredit', 'total_credit')->sortable(),
            Number::make('companyId', 'company_id'),
            Str::make('status')->sortable(),
            DateTime::make('approvedAt')->sortable(),
            Number::make('approvedById'),
            DateTime::make('postedAt', 'posted_at')->sortable(),
            Number::make('postedById', 'posted_by_id'),
            Number::make('reversalOfId'),
            Str::make('reversalReason'),
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('journal'),
            // Relationships
            BelongsTo::make('fiscalPeriod'),
            // Relationships
            BelongsTo::make('journalEntry'),
            // Relationships
            HasMany::make('journalLines'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('reference'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'journal',
            'fiscalPeriod',
            'journalEntry',
            'journalLines',
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
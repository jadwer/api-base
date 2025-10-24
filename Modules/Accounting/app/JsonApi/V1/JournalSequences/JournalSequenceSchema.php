<?php

namespace Modules\Accounting\JsonApi\V1\JournalSequences;

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
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceSchema extends Schema
{
    public static string $model = JournalSequence::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('journalId'),
            Number::make('fiscalYear')->sortable(),
            Number::make('currentNumber')->sortable(),
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('journal'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('fiscal_year'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('current_number'),
        ];
    }

    public function sortables(): array
    {
        return [
            'fiscal_year',
            'current_number',
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [
            'journal',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "journal-sequences";
    }
}
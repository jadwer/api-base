<?php

namespace Modules\Accounting\JsonApi\V1\Journals;

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
use Modules\Accounting\Models\Journal;

class JournalSchema extends Schema
{
    public static string $model = Journal::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Str::make('code')->sortable(),
            Str::make('name')->sortable(),
            Boolean::make('autoNumbering')->sortable(),
            Str::make('sequencePrefix')->sortable(),
            Number::make('sequenceNext')->sortable(),
            Str::make('defaultCurrency')->sortable(),
            Str::make('postPolicy')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('code'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('auto_numbering'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('sequence_prefix'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('sequence_next'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('default_currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('post_policy'),
        ];
    }

    public function sortables(): array
    {
        return [
            'code',
            'name',
            'auto_numbering',
            'sequence_prefix',
            'sequence_next',
            'default_currency',
            'post_policy',
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
        return "journals";
    }
}
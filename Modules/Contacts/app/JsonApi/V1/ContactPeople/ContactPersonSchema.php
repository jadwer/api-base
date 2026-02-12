<?php

namespace Modules\Contacts\JsonApi\V1\ContactPeople;

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
use Modules\Contacts\Models\ContactPerson;

class ContactPersonSchema extends Schema
{
    public static string $model = ContactPerson::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('contactId', 'contact_id')->sortable(),
            Str::make('name')->sortable(),
            Str::make('position')->sortable(),
            Str::make('department')->sortable(),
            Str::make('email')->sortable(),
            Str::make('phone')->sortable(),
            Str::make('mobile')->sortable(),
            Boolean::make('isPrimary', 'is_primary')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('contactId', 'contact_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('position'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('department'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('email'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('phone'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('mobile'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_primary')->asBoolean(),
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
        return "contact-people";
    }
}
<?php

namespace Modules\Contacts\JsonApi\V1\ContactAddresses;

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
use Modules\Contacts\Models\ContactAddress;

class ContactAddressSchema extends Schema
{
    public static string $model = ContactAddress::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('contactId', 'contact_id')->sortable(),
            Str::make('addressType', 'address_type')->sortable(),
            Str::make('addressLine1', 'address_line_1')->sortable(),
            Str::make('addressLine2', 'address_line_2')->sortable(),
            Str::make('city')->sortable(),
            Str::make('state')->sortable(),
            Str::make('country')->sortable(),
            Str::make('postalCode', 'postal_code')->sortable(),
            Boolean::make('isDefault', 'is_default')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('address_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('address_line_1'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('address_line_2'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('city'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('state'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('country'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('postal_code'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_default'),
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
        return "contact-addresses";
    }
}
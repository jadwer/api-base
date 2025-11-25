<?php

namespace Modules\CRM\JsonApi\V1\Leads;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\CRM\Models\Lead;

class LeadSchema extends Schema
{
    public static string $model = Lead::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('title')->sortable(),
            Str::make('source')->sortable(),
            Str::make('status')->sortable(),
            Str::make('rating')->sortable(),
            Str::make('companyName', 'company_name')->sortable(),
            Str::make('contactPerson', 'contact_person'),
            Str::make('email')->sortable(),
            Str::make('phone'),
            Number::make('estimatedValue', 'estimated_value')->sortable(),
            Str::make('estimatedCloseDate', 'estimated_close_date')->sortable(),
            DateTime::make('convertedAt', 'converted_at')->sortable(),
            Str::make('notes'),
            ArrayHash::make('metadata'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            // BelongsTo::make('contact')
            //     ->type('contacts')
            //     ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),
            // TODO: Enable when Contact module is implemented

            BelongsTo::make('user')
                ->type('users')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            // BelongsToMany::make('campaigns')
            //     ->type('campaigns')
            //     ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),
            // TODO: Enable when Campaign entity is implemented

            HasMany::make('activities')
                ->type('activities')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            // HasOne::make('opportunity')
            //     ->type('opportunities')
            //     ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),
            // TODO: Enable when Opportunity entity is implemented
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('title'),
            Where::make('source'),
            Where::make('status'),
            Where::make('rating'),
            Where::make('companyName', 'company_name'),
            Where::make('email'),
            Where::make('userId', 'user_id'),
            // Where::make('contactId', 'contact_id'), // TODO: Enable when Contact module is implemented
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'leads';
    }
}

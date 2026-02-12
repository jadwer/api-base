<?php

namespace Modules\Product\JsonApi\V1\VariantAttributes;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use Modules\Product\Models\VariantAttribute;

/**
 * PR-M003: JSON:API Schema for VariantAttribute.
 */
class VariantAttributeSchema extends Schema
{
    public static string $model = VariantAttribute::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('code')->sortable(),
            Str::make('description'),
            Boolean::make('isActive', 'is_active')->sortable(),
            Number::make('sortOrder', 'sort_order')->sortable(),

            // Relationships
            HasMany::make('values')->type('variant-attribute-values'),

            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('code'),
            Where::make('is_active')->asBoolean(),
        ];
    }

    public function includePaths(): array
    {
        return [
            'values',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'variant-attributes';
    }
}

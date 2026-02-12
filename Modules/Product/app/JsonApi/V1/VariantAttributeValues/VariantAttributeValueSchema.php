<?php

namespace Modules\Product\JsonApi\V1\VariantAttributeValues;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use Modules\Product\Models\VariantAttributeValue;

/**
 * PR-M003: JSON:API Schema for VariantAttributeValue.
 */
class VariantAttributeValueSchema extends Schema
{
    public static string $model = VariantAttributeValue::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('value')->sortable(),
            Str::make('code')->sortable(),
            Str::make('displayValue', 'display_value'),
            Str::make('colorHex', 'color_hex'),
            Number::make('sortOrder', 'sort_order')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),

            // Relationships
            BelongsTo::make('variantAttribute')->type('variant-attributes')->readOnly(),
            BelongsToMany::make('productVariants')->type('product-variants'),

            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('value'),
            Where::make('code'),
            Where::make('variant_attribute_id'),
            Where::make('is_active')->asBoolean(),
        ];
    }

    public function includePaths(): array
    {
        return [
            'variantAttribute',
            'productVariants',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'variant-attribute-values';
    }
}

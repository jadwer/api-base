<?php

namespace Modules\Product\JsonApi\V1\ProductVariants;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Scope;
use Modules\Product\Models\ProductVariant;

/**
 * PR-M003: JSON:API Schema for ProductVariant.
 */
class ProductVariantSchema extends Schema
{
    public static string $model = ProductVariant::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('sku')->sortable(),
            Str::make('name'),
            Number::make('price')->sortable(),
            Number::make('cost')->sortable(),
            Number::make('weight'),
            Str::make('barcode'),
            Str::make('imgPath', 'img_path'),
            Number::make('stockQuantity', 'stock_quantity')->sortable(),
            Number::make('lowStockThreshold', 'low_stock_threshold'),
            Boolean::make('isActive', 'is_active')->sortable(),
            Boolean::make('isDefault', 'is_default')->sortable(),
            ArrayHash::make('metadata'),

            // Computed fields
            Number::make('effectivePrice')->readOnly(),
            Number::make('effectiveCost')->readOnly(),
            Str::make('displayName')->readOnly(),

            // Relationships
            BelongsTo::make('product')->type('products'),
            BelongsToMany::make('attributeValues')->type('variant-attribute-values'),

            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('sku'),
            Where::make('product_id'),
            Where::make('is_active'),
            Where::make('is_default'),
            Scope::make('active'),
            Scope::make('inStock'),
            Scope::make('lowStock'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'product',
            'attributeValues',
            'attributeValues.variantAttribute',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'product-variants';
    }
}

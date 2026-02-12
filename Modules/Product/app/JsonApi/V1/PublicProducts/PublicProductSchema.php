<?php

namespace Modules\Product\JsonApi\V1\PublicProducts;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;

class PublicProductSchema extends Schema
{
    public static string $model = Product::class;

    /**
     * Only show active products in the public catalog.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('sku')->sortable(),
            Str::make('description'),
            Str::make('fullDescription', 'full_description'),
            Number::make('price')->sortable(),
            Number::make('compareAtPrice', 'compare_at_price'),
            Boolean::make('isOnSale', 'is_on_sale'),
            DateTime::make('saleStartsAt', 'sale_starts_at'),
            DateTime::make('saleEndsAt', 'sale_ends_at'),
            Str::make('saleBadge', 'sale_badge'),
            Boolean::make('iva'),
            Str::make('imgPath', 'img_path'),
            Str::make('datasheetPath', 'datasheet_path'),
            Str::make('imageUrl', 'img_path')->readOnly()->extractUsing(
                static fn($model, $column, $value) => $model->img_url
            ),
            Str::make('datasheetUrl', 'datasheet_path')->readOnly()->extractUsing(
                static fn($model, $column, $value) => $model->datasheet_url
            ),

            // Relaciones
            BelongsTo::make('unit')->type('units'),
            BelongsTo::make('category')->type('categories'),
            BelongsTo::make('brand')->type('brands'),
            HasMany::make('images')->type('product-images')->readOnly(),

            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('sku'),
            Where::make('search_name', 'name')->deserializeUsing(
                static fn($value) => "%{$value}%"
            )->using('like'),
            Where::make('search_sku', 'sku')->deserializeUsing(
                static fn($value) => "%{$value}%"
            )->using('like'),
            Where::make('search_description', 'description')->deserializeUsing(
                static fn($value) => "%{$value}%"
            )->using('like'),
            Scope::make('search'),
            Where::make('unit_id'),
            Where::make('category_id'),
            Where::make('brand_id'),
            Where::make('is_on_sale')->asBoolean(),
            Scope::make('onSale', 'on_sale'),
            WhereIn::make('brands', 'brand_id')->delimiter(','),
            WhereIn::make('categories', 'category_id')->delimiter(','),
            WhereIn::make('units', 'unit_id')->delimiter(','),
        ];
    }

    public function includePaths(): array
    {
        return [
            'unit',
            'category',
            'brand',
            'images',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'public-products';
    }

    public static function authorizer(): string
    {
        return PublicProductAuthorizer::class;
    }
}
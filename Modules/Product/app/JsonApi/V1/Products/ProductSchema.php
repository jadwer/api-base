<?php

namespace Modules\Product\JsonApi\V1\Products;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use Modules\Product\Models\Product;

class ProductSchema extends Schema
{
    public static string $model = Product::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('sku')->sortable(),
            Str::make('description'),
            Str::make('fullDescription', 'full_description'),
            Number::make('price')->sortable(),
            Number::make('cost')->sortable(),
            Boolean::make('iva'),
            Str::make('imgPath', 'img_path'),
            Str::make('datasheetPath', 'datasheet_path'),
            
            // Relaciones
            BelongsTo::make('unit')->type('units'),
            BelongsTo::make('category')->type('categories'),
            BelongsTo::make('brand')->type('brands'),
            
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('sku'),
            Where::make('unit_id'),
            Where::make('category_id'),
            Where::make('brand_id'),
            WhereIn::make('brand_id', 'brands'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'unit',
            'category', 
            'brand',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'products';
    }
}

<?php

namespace Modules\Product\JsonApi\V1\Brands;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use Modules\Product\Models\Brand;

class BrandSchema extends Schema
{
    public static string $model = Brand::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('description'),
            Str::make('slug')->sortable(),
            
            // Relaciones
            HasMany::make('products')->type('products'),
            
            DateTime::make('created_at')->readOnly()->sortable(),
            DateTime::make('updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function includePaths(): array
    {
        return [
            'products',
        ];
    }

    public static function type(): string
    {
        return 'brands';
    }
}

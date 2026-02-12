<?php

namespace Modules\Product\JsonApi\V1\PublicProductImages;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use Modules\Product\Models\ProductImage;

class PublicProductImageSchema extends Schema
{
    public static string $model = ProductImage::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('filePath', 'file_path')->readOnly(),
            Str::make('imageUrl')->readOnly()->extractUsing(
                static fn($model) => $model->image_url
            ),
            Str::make('altText', 'alt_text')->readOnly(),
            Number::make('sortOrder', 'sort_order')->readOnly(),
            Boolean::make('isPrimary', 'is_primary')->readOnly(),

            BelongsTo::make('product')->type('public-products')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('product_id'),
            Where::make('is_primary')->asBoolean(),
        ];
    }

    public function includePaths(): array
    {
        return [];
    }

    public static function type(): string
    {
        return 'product-images';
    }
}

<?php

namespace Modules\Product\JsonApi\V1\ProductImages;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use Modules\Product\Models\ProductImage;

class ProductImageSchema extends Schema
{
    public static string $model = ProductImage::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('filePath', 'file_path'),
            Str::make('imageUrl')->readOnly()->extractUsing(
                static fn($model) => $model->image_url
            ),
            Str::make('altText', 'alt_text'),
            Number::make('sortOrder', 'sort_order')->sortable(),
            Boolean::make('isPrimary', 'is_primary'),

            BelongsTo::make('product')->type('products'),

            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
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
        return [
            'product',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'product-images';
    }
}

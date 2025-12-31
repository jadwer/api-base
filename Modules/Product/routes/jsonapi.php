<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Product\Http\Controllers\Api\V1\ProductController;
use Modules\Product\Http\Controllers\Api\V1\UnitController;
use Modules\Product\Http\Controllers\Api\V1\CategoryController;
use Modules\Product\Http\Controllers\Api\V1\BrandController;
use Modules\Product\Http\Controllers\Api\V1\VariantAttributeController;
use Modules\Product\Http\Controllers\Api\V1\VariantAttributeValueController;
use Modules\Product\Http\Controllers\Api\V1\ProductVariantController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('products', ProductController::class);
        $server->resource('units', UnitController::class);
        $server->resource('categories', CategoryController::class);
        $server->resource('brands', BrandController::class);

        // PR-M003: Product Variants
        $server->resource('variant-attributes', VariantAttributeController::class)
            ->relationships(function ($relationships) {
                $relationships->hasMany('values');
            });
        $server->resource('variant-attribute-values', VariantAttributeValueController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('variantAttribute');
                $relationships->hasMany('productVariants');
            });
        $server->resource('product-variants', ProductVariantController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('product');
                $relationships->hasMany('attributeValues');
            });
    });

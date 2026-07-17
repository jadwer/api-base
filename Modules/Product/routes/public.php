<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Product\Http\Controllers\Api\V1\PublicProductController;
use Modules\Product\Http\Controllers\Api\V1\PublicCategoryController;

/*
|--------------------------------------------------------------------------
| Public JSON:API Routes
|--------------------------------------------------------------------------
|
| These routes are publicly accessible without authentication.
| Registered under the 'public' server which has baseUri '/api/public/v1'
| defined in app/JsonApi/V1/PublicServer.php
|
*/

JsonApiRoute::server('public')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('public-products', PublicProductController::class)
            ->only('index', 'show')
            ->relationships(function ($relationships) {
                $relationships->hasOne('unit')->readOnly();
                $relationships->hasOne('category')->readOnly();
                $relationships->hasOne('brand')->readOnly();
                $relationships->hasMany('images')->readOnly();
            });

        // Public read-only categories for the footer/menu navigation of the
        // public catalog (the authenticated /api/v1/categories returns 401 to
        // guests). Uses the dedicated PublicCategorySchema (type
        // 'public-categories'), index+show only.
        $server->resource('public-categories', PublicCategoryController::class)
            ->only('index', 'show');
    });

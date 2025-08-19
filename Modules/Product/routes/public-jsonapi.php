<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Product\Http\Controllers\Api\V1\PublicProductController;

/*
|--------------------------------------------------------------------------
| Public JSON:API Routes
|--------------------------------------------------------------------------
|
| These routes are publicly accessible without authentication.
| Used for product catalog display in frontend applications following
| JSON:API 5.x specification for consistency with the rest of the API.
|
*/

JsonApiRoute::server('public')
    ->prefix('public/v1')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('public-products', PublicProductController::class)
            ->only('index', 'show')
            ->relationships(function ($relationships) {
                $relationships->hasOne('unit')->readOnly();
                $relationships->hasOne('category')->readOnly();
                $relationships->hasOne('brand')->readOnly();
            });
    });
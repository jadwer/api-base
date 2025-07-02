<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\PageBuilder\Http\Controllers\Api\V1\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Modules\PageBuilder\JsonApi\V1\Pages\PageResource;
use Illuminate\Support\Facades\Route;




/*
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
        JsonApiRoute::server('v1')
            ->prefix('v1')
            ->resources(function (ResourceRegistrar $server) {
                $server->resource('users', UserController::class);
            });
});

*/
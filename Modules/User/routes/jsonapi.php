<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\User\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Modules\User\JsonApi\V1\Users\UserResource;
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
<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\PermissionManager\Http\Controllers\Api\V1\RoleController;
use Modules\PermissionManager\Http\Controllers\Api\V1\PermissionController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('roles', RoleController::class)->relationships(function ($relationships) {
            $relationships->hasMany('permissions')->readOnly(false);});
        $server->resource('permissions', PermissionController::class);
    });

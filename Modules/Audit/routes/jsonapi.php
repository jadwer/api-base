<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\Api\V1\AuditController;

/*
Route::middleware(['auth:sanctum'])->prefix('v1')
    ->group(function () {
        JsonApiRoute::server('v1')
            ->prefix('v1')
            ->resources(function (ResourceRegistrar $server) {
                $server->resource('audits', AuditController::class);
            });
    });

    */
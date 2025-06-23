<?php

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\Api\V1\ActivityController as ActivityController;
use Modules\Audit\Http\Controllers\AuditController;

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;


/*
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('audits', AuditController::class)->names('audit');
});
*/

/*

Route::middleware(['auth:sanctum'])
->prefix('v1/audits')
->group(function () {
    Route::get('logs', [AuditController::class, 'index']);
});

*/
/*
JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function ($server) {
        $server->resource('audits', \Modules\Audit\Http\Controllers\Api\V1\AuditController::class);
    });
    */
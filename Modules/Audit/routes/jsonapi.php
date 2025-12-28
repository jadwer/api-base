<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Audit\Http\Controllers\Api\V1\AuditController;

/*
|--------------------------------------------------------------------------
| Audit Module JSON:API Routes
|--------------------------------------------------------------------------
|
| Audit logs are read-only by design. Only index and show operations
| are available. Creation happens automatically via LogsActivity trait.
|
*/

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('audits', AuditController::class)
            ->only('index', 'show')
            ->readOnly();
    });
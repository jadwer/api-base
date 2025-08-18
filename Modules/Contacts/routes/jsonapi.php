<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Contacts\Http\Controllers\Api\V1\ContactController;
use Modules\Contacts\Http\Controllers\Api\V1\ContactDocumentController;
use Modules\Contacts\Http\Controllers\Api\V1\ContactAddressController;
use Modules\Contacts\Http\Controllers\Api\V1\ContactPersonController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('contacts', ContactController::class);
        $server->resource('contact-documents', ContactDocumentController::class);
        $server->resource('contact-addresses', ContactAddressController::class);
        $server->resource('contact-people', ContactPersonController::class);
    });

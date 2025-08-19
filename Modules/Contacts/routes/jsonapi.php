<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Contacts\Http\Controllers\Api\V1\ContactController;
use Modules\Contacts\Http\Controllers\Api\V1\ContactDocumentController;
use Modules\Contacts\Http\Controllers\Api\V1\ContactAddressController;
use Modules\Contacts\Http\Controllers\Api\V1\ContactPersonController;
use Modules\Contacts\Http\Controllers\Api\V1\ContactDocumentUploadController;
use Illuminate\Support\Facades\Route;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('contacts', ContactController::class);
        $server->resource('contact-documents', ContactDocumentController::class);
        $server->resource('contact-addresses', ContactAddressController::class);
        $server->resource('contact-people', ContactPersonController::class);
    });

// TODO: Refactorizar con JSON:API de manera empresarial
// - Estas rutas son "pegotes" fuera del estándar JSON:API
// - Migrar a Actions dentro del ContactDocumentController JSON:API estándar
// - Usar PATCH /contact-documents/{id} con meta actions en lugar de rutas custom
// - Implementar según JSON:API specification para file handling
// - Eliminar estas rutas custom y usar solo resource routes estándar

// Custom upload routes (authentication handled manually in controller)
Route::prefix('v1')->group(function () {
    Route::post('contact-documents/upload', [ContactDocumentUploadController::class, 'store'])->name('contact-documents.upload');
    Route::get('contact-documents/{document}/download', [ContactDocumentUploadController::class, 'download'])->name('contact-documents.download');
    Route::get('contact-documents/{document}/view', [ContactDocumentUploadController::class, 'view'])->name('contact-documents.view');
    Route::patch('contact-documents/{document}/verify', [ContactDocumentUploadController::class, 'verify'])->name('contact-documents.verify');
    Route::patch('contact-documents/{document}/unverify', [ContactDocumentUploadController::class, 'unverify'])->name('contact-documents.unverify');
});

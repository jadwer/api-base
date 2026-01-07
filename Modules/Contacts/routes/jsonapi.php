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

/**
 * Custom file handling routes for contact documents.
 * These routes use standard Laravel file handling because JSON:API spec
 * doesn't natively support multipart/form-data uploads.
 * Authentication is handled manually in the controller.
 */
Route::prefix('v1')->group(function () {
    Route::post('contact-documents/upload', [ContactDocumentUploadController::class, 'store'])->name('contact-documents.upload');
    Route::get('contact-documents/{document}/download', [ContactDocumentUploadController::class, 'download'])->name('contact-documents.download');
    Route::get('contact-documents/{document}/view', [ContactDocumentUploadController::class, 'view'])->name('contact-documents.view');
    Route::patch('contact-documents/{document}/verify', [ContactDocumentUploadController::class, 'verify'])->name('contact-documents.verify');
    Route::patch('contact-documents/{document}/unverify', [ContactDocumentUploadController::class, 'unverify'])->name('contact-documents.unverify');
});

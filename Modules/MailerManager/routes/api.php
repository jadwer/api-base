<?php

use Illuminate\Support\Facades\Route;
use Modules\MailerManager\Http\Controllers\Api\V1\EmailTemplateController;
use Modules\MailerManager\Http\Controllers\Api\V1\SystemEmailController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Email Template custom endpoints
    Route::post('email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview']);
    Route::post('email-templates/{emailTemplate}/send-test', [EmailTemplateController::class, 'sendTest']);
    Route::post('email-templates/{emailTemplate}/upload-image', [EmailTemplateController::class, 'uploadImage']);
    Route::delete('email-templates/{emailTemplate}/images/{image}', [EmailTemplateController::class, 'deleteImage']);

    // System Email custom endpoints
    Route::post('system-emails/{systemEmail}/preview', [SystemEmailController::class, 'preview']);
    Route::post('system-emails/{systemEmail}/send-test', [SystemEmailController::class, 'sendTest']);
});

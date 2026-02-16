<?php

use Illuminate\Support\Facades\Route;
use Modules\AppConfig\Http\Controllers\Api\V1\AppSettingController;

Route::prefix('v1')->group(function () {
    // Public: company + branding settings (for landing/store)
    Route::get('app-settings/public', [AppSettingController::class, 'publicSettings']);

    // Admin only
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('app-settings', [AppSettingController::class, 'index']);
        Route::get('app-settings/group/{group}', [AppSettingController::class, 'byGroup']);
        Route::patch('app-settings/{key}', [AppSettingController::class, 'update'])
            ->where('key', '[a-zA-Z0-9_.]+');
    });
});

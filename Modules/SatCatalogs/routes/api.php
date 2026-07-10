<?php

use Illuminate\Support\Facades\Route;
use Modules\SatCatalogs\Http\Controllers\Api\V1\SatCatalogController;

/*
|--------------------------------------------------------------------------
| SatCatalogs Module API Routes
|--------------------------------------------------------------------------
|
| Read-only search endpoints over the self-hosted SAT catalogs, used by
| dynamic dropdowns in the admin UI (product form, invoicing).
|
*/

Route::middleware('auth:sanctum')->prefix('v1/sat')->name('sat.')->group(function () {
    Route::get('clave-prod-serv', [SatCatalogController::class, 'claveProdServ'])->name('clave-prod-serv');
    Route::get('clave-unidad', [SatCatalogController::class, 'claveUnidad'])->name('clave-unidad');
    Route::get('forma-pago', [SatCatalogController::class, 'formaPago'])->name('forma-pago');
    Route::get('tasa-o-cuota', [SatCatalogController::class, 'tasaOCuota'])->name('tasa-o-cuota');
});

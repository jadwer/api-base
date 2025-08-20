<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\Api\V1\ReportController;

/*
|--------------------------------------------------------------------------
| Accounting Reports API Routes
|--------------------------------------------------------------------------
*/

Route::group([
    'middleware' => ['auth:sanctum'],
    'prefix' => 'api/v1/accounting/reports'
], function () {
    
    // Balance General (Balance Sheet)
    Route::get('balance-general', [ReportController::class, 'balanceGeneral'])
        ->middleware('can:accounting.reports.balance-general');
    
    // Estado de Resultados (Income Statement)
    Route::get('estado-resultados', [ReportController::class, 'estadoResultados'])
        ->middleware('can:accounting.reports.income-statement');
    
    // Balanza de Comprobación (Trial Balance)
    Route::get('balanza-comprobacion', [ReportController::class, 'balanzaComprobacion'])
        ->middleware('can:accounting.reports.trial-balance');
    
    // Libro Diario (General Journal)
    Route::get('libro-diario', [ReportController::class, 'libroDiario'])
        ->middleware('can:accounting.reports.general-journal');
    
    // Libro Mayor (General Ledger)
    Route::get('libro-mayor', [ReportController::class, 'libroMayor'])
        ->middleware('can:accounting.reports.general-ledger');
    
});
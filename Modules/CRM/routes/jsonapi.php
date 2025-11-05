<?php

use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

/*
|--------------------------------------------------------------------------
| JSON:API Routes - CRM Module
|--------------------------------------------------------------------------
|
| Here is where you can register JSON:API routes for the CRM module.
| These routes are loaded by the RouteServiceProvider within a group
| which contains the "api" middleware group and JSON:API middleware.
|
*/

// JSON:API Routes with auth:sanctum middleware
JsonApiRoute::server('v1')
    ->middleware('auth:sanctum')
    ->prefix('v1')
    ->resources(function ($server) {
        // Leads - Lead management
        $server->resource('leads', \Modules\CRM\Http\Controllers\Api\V1\LeadController::class);

        // Opportunities - Sales opportunities
        // $server->resource('opportunities', JsonApiController::class);

        // Quotes - Sales quotes/proposals
        // $server->resource('quotes', JsonApiController::class);

        // Quote Items - Line items in quotes
        // $server->resource('quote-items', JsonApiController::class);

        // Activities - Interaction tracking (calls, emails, meetings)
        // $server->resource('activities', JsonApiController::class);

        // Campaigns - Marketing campaigns
        $server->resource('campaigns', \Modules\CRM\Http\Controllers\Api\V1\CampaignController::class);

        // Pipeline Stages - Pipeline configuration
        $server->resource('pipeline-stages', \Modules\CRM\Http\Controllers\Api\V1\PipelineStageController::class);
    });

// Custom CRM endpoints (will be added in Phase 4)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Lead operations
    // Route::post('leads/{lead}/convert', [\Modules\CRM\Http\Controllers\Api\V1\LeadController::class, 'convert']);
    // Route::post('leads/{lead}/qualify', [\Modules\CRM\Http\Controllers\Api\V1\LeadController::class, 'qualify']);

    // Opportunity operations
    // Route::post('opportunities/{opportunity}/advance-stage', [\Modules\CRM\Http\Controllers\Api\V1\OpportunityController::class, 'advanceStage']);
    // Route::post('opportunities/{opportunity}/close-won', [\Modules\CRM\Http\Controllers\Api\V1\OpportunityController::class, 'closeWon']);

    // Quote operations
    // Route::post('quotes/{quote}/send', [\Modules\CRM\Http\Controllers\Api\V1\QuoteController::class, 'send']);
    // Route::post('quotes/{quote}/accept', [\Modules\CRM\Http\Controllers\Api\V1\QuoteController::class, 'accept']);

    // Campaign operations
    // Route::post('campaigns/{campaign}/add-leads', [\Modules\CRM\Http\Controllers\Api\V1\CampaignController::class, 'addLeads']);
});

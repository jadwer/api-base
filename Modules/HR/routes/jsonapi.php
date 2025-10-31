<?php

use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/*
|--------------------------------------------------------------------------
| JSON:API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register JSON:API routes for the HR module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $api) {

        // Department Resources
        $api->resource('departments', \Modules\HR\Http\Controllers\Api\V1\DepartmentController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('manager');
                $relationships->hasMany('employees');
                $relationships->hasMany('positions');
            });

        // Position Resources
        $api->resource('positions', \Modules\HR\Http\Controllers\Api\V1\PositionController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('department');
                $relationships->hasMany('employees');
            });

        // Employee Resources
        $api->resource('employees', \Modules\HR\Http\Controllers\Api\V1\EmployeeController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('department');
                $relationships->hasOne('position');
                $relationships->hasOne('user');
                $relationships->hasMany('managedDepartments');
                $relationships->hasMany('attendances');
                $relationships->hasMany('leaves');
                $relationships->hasMany('payrollItems');
                $relationships->hasMany('performanceReviews');
            });

        // Attendance Resources
        $api->resource('attendances', \Modules\HR\Http\Controllers\Api\V1\AttendanceController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('employee');
            });

        // LeaveType Resources
        $api->resource('leave-types', \Modules\HR\Http\Controllers\Api\V1\LeaveTypeController::class)
            ->relationships(function ($relationships) {
                $relationships->hasMany('leaves');
            });

        // Leave Resources
        $api->resource('leaves', \Modules\HR\Http\Controllers\Api\V1\LeaveController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('employee');
                $relationships->hasOne('leaveType');
                $relationships->hasOne('approver');
            });

        // PayrollPeriod Resources
        $api->resource('payroll-periods', \Modules\HR\Http\Controllers\Api\V1\PayrollPeriodController::class)
            ->relationships(function ($relationships) {
                $relationships->hasMany('payrollItems');
            });

        // PayrollItem Resources
        $api->resource('payroll-items', \Modules\HR\Http\Controllers\Api\V1\PayrollItemController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('employee');
                $relationships->hasOne('payrollPeriod');
            });

        // PerformanceReview Resources
        $api->resource('performance-reviews', \Modules\HR\Http\Controllers\Api\V1\PerformanceReviewController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('employee');
                $relationships->hasOne('reviewer');
            });
    });

// Payroll Period Business Logic Routes (outside JSON:API convention)
Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('payroll-periods/{payrollPeriod}/process', [\Modules\HR\Http\Controllers\Api\V1\PayrollPeriodController::class, 'process']);
        Route::post('payroll-periods/{payrollPeriod}/mark-as-paid', [\Modules\HR\Http\Controllers\Api\V1\PayrollPeriodController::class, 'markAsPaid']);
        Route::post('payroll-periods/{payrollPeriod}/close', [\Modules\HR\Http\Controllers\Api\V1\PayrollPeriodController::class, 'close']);
        Route::post('payroll-periods/{payrollPeriod}/reopen', [\Modules\HR\Http\Controllers\Api\V1\PayrollPeriodController::class, 'reopen']);
    });

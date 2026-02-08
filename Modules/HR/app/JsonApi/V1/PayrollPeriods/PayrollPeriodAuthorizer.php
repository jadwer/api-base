<?php

namespace Modules\HR\JsonApi\V1\PayrollPeriods;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use Modules\HR\Models\PayrollPeriod;

class PayrollPeriodAuthorizer implements AuthorizerContract
{
    /**
     * Authorize index (list) requests.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.payroll-periods.index') ?? false;
    }

    /**
     * Authorize store (create) requests.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.payroll-periods.store') ?? false;
    }

    /**
     * Authorize show (view) requests.
     */
    public function show(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.payroll-periods.show') ?? false;
    }

    /**
     * Authorize update requests.
     */
    public function update(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.payroll-periods.update') ?? false;
    }

    /**
     * Authorize destroy (delete) requests.
     */
    public function destroy(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.payroll-periods.destroy') ?? false;
    }

    /**
     * Authorize showRelated requests.
     */
    public function showRelated(Request $request, $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize showRelationship requests.
     */
    public function showRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize updateRelationship requests.
     */
    public function updateRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize attachRelationship requests.
     */
    public function attachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize detachRelationship requests.
     */
    public function detachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}

<?php

namespace Modules\HR\JsonApi\V1\PayrollItems;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;

class PayrollItemAuthorizer implements AuthorizerContract
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.store') ?? false;
    }

    public function show(Request $request, $model): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.show') ?? false;
    }

    public function update(Request $request, $model): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.update') ?? false;
    }

    public function destroy(Request $request, $model): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.destroy') ?? false;
    }

    public function showRelated(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.show-related') ?? false;
    }

    public function showRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.show-relationship') ?? false;
    }

    public function updateRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.update-relationship') ?? false;
    }

    public function attachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.attach-relationship') ?? false;
    }

    public function detachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.payroll-items.detach-relationship') ?? false;
    }
}

<?php

namespace Modules\HR\JsonApi\V1\Employees;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class EmployeeAuthorizer implements Authorizer
{
    // =========================================================================
    // 5 CRUD METHODS
    // =========================================================================

    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.destroy') ?? false;
    }

    // =========================================================================
    // 5 RELATIONSHIP METHODS (CRITICAL - DON'T FORGET THESE!)
    // =========================================================================

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}

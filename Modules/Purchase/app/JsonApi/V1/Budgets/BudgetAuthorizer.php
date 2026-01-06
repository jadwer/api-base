<?php

namespace Modules\Purchase\JsonApi\V1\Budgets;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class BudgetAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('budgets.index') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('budgets.show') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('budgets.store') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('budgets.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('budgets.destroy') ?? false;
    }

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

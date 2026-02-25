<?php

namespace Modules\Ecommerce\JsonApi\V1\Currencies;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CurrencyAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return true;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.currencies.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        return true;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('ecommerce.currencies.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('ecommerce.currencies.destroy') ?? false;
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

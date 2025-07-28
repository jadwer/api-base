<?php

namespace Modules\Sales\JsonApi\V1\SalesOrderItems;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class SalesOrderItemsAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('sales-order-items.index', 'api') ?? false;
    }
    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('sales-order-items.store', 'api') ?? false;
    }
    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('sales-order-items.view', 'api') ?? false;
    }
    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('sales-order-items.update', 'api') ?? false;
    }
    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('sales-order-items.destroy', 'api') ?? false;
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

<?php

namespace Modules\Inventory\JsonApi\V1\Stocks;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class StockAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('stock.index') ?? false;
    }

    /**
     * Authorize the store controller action.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('stock.store') ?? false;
    }

    /**
     * Authorize the show controller action.
     */
    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('stock.show') ?? false;
    }

    /**
     * Authorize the update controller action.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('stock.update') ?? false;
    }

    /**
     * Authorize the destroy controller action.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('stock.destroy') ?? false;
    }

    /**
     * Authorize a relationship to be shown.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize a relationship to be shown.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize a relationship to be updated.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize a relationship to be attached.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize a relationship to be detached.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}

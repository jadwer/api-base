<?php

namespace Modules\Inventory\JsonApi\V1\Warehouses;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class WarehouseAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('warehouses.index') ?? false;
    }

    /**
     * Authorize the store controller action.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('warehouses.store') ?? false;
    }

    /**
     * Authorize the show controller action.
     */
    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('warehouses.view') ?? false;
    }

    /**
     * Authorize the update controller action.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('warehouses.update') ?? false;
    }

    /**
     * Authorize the destroy controller action.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('warehouses.destroy') ?? false;
    }

    /**
     * Authorize the show-related controller action.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize the show-relationship controller action.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize the update-relationship controller action.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize the attach-relationship controller action.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize the detach-relationship controller action.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}

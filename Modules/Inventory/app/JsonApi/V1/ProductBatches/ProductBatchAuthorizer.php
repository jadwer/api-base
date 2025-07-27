<?php

namespace Modules\Inventory\JsonApi\V1\ProductBatches;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductBatchAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('product-batch.index') ?? false;
    }

    /**
     * Authorize the store controller action.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('product-batch.store') ?? false;
    }

    /**
     * Authorize the show controller action.
     */
    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('product-batch.show') ?? false;
    }

    /**
     * Authorize the update controller action.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('product-batch.update') ?? false;
    }

    /**
     * Authorize the destroy controller action.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('product-batch.destroy') ?? false;
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

<?php

namespace Modules\Sales\JsonApi\V1\SalesOrderItems;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class SalesOrderItemAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action (GET /api/v1/sales-order-items)
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('sales-order-items.index') ?? false;
    }

    /**
     * Authorize the store controller action (POST /api/v1/sales-order-items)
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('sales-order-items.store') ?? false;
    }

    /**
     * Authorize the show controller action (GET /api/v1/sales-order-items/{id})
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('sales-order-items.show') ?? false;
    }

    /**
     * Authorize the update controller action (PATCH /api/v1/sales-order-items/{id})
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('sales-order-items.update') ?? false;
    }

    /**
     * Authorize the destroy controller action (DELETE /api/v1/sales-order-items/{id})
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('sales-order-items.destroy') ?? false;
    }

    // Relationship methods
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('sales-order-items.show') ?? false;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('sales-order-items.show') ?? false;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('sales-order-items.update') ?? false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('sales-order-items.update') ?? false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('sales-order-items.update') ?? false;
    }
}

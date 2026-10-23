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
        $order = \App\Support\DocumentItemLock::parentFromRequest($request, \Modules\Sales\Models\SalesOrder::class, 'salesOrderId', 'salesOrder');

        return \App\Support\DocumentItemLock::authorize($user?->can('sales-order-items.store') ?? false, $order, \Modules\Sales\Models\SalesOrder::LOCKED_ITEM_STATUSES, 'el pedido');
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

        return \App\Support\DocumentItemLock::authorize($user?->can('sales-order-items.update') ?? false, $model->salesOrder, \Modules\Sales\Models\SalesOrder::LOCKED_ITEM_STATUSES, 'el pedido');
    }

    /**
     * Authorize the destroy controller action (DELETE /api/v1/sales-order-items/{id})
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        return \App\Support\DocumentItemLock::authorize($user?->can('sales-order-items.destroy') ?? false, $model->salesOrder, \Modules\Sales\Models\SalesOrder::LOCKED_ITEM_STATUSES, 'el pedido');
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

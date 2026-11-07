<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class SalesOrderAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('sales-orders.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('sales-orders.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        // Personal con el permiso ve todo; el cliente del portal solo sus pedidos
        // (misma regla que cotizaciones y que indexQuery).
        if ($user->can('sales-orders.show') && \Modules\User\Support\UserAccess::seesAll($user, 'sales-orders.index')) {
            return true;
        }

        return \Modules\User\Support\UserAccess::seesDocument($user, 'sales-orders.index', $model->contact_id);
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('sales-orders.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('sales-orders.destroy') ?? false;
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

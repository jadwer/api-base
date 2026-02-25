<?php

namespace Modules\Inventory\JsonApi\V1\InventoryMovements;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class InventoryMovementAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('inventory-movements.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('inventory-movements.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('inventory-movements.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        if ($model->status === 'completed') {
            return Response::deny('No se pueden modificar movimientos completados.');
        }
        return $request->user()?->can('inventory-movements.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        if ($model->status === 'completed') {
            return Response::deny('No se pueden eliminar movimientos completados.');
        }
        return $request->user()?->can('inventory-movements.destroy') ?? false;
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

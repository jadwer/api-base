<?php

namespace Modules\Inventory\JsonApi\V1\InventoryMovements;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class InventoryMovementAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action (GET /api/v1/inventory-movements)
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot list inventory movements
        if ($user && $user->hasRole('customer')) {
            return false;
        }
        
        return $user?->can('inventory-movements.index') ?? false;
    }

    /**
     * Authorize the store controller action (POST /api/v1/inventory-movements)
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot create inventory movements
        if ($user && $user->hasRole('customer')) {
            return false;
        }
        
        return $user?->can('inventory-movements.store') ?? false;
    }

    /**
     * Authorize the show controller action (GET /api/v1/inventory-movements/{id})
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot view inventory movements
        if ($user && $user->hasRole('customer')) {
            return false;
        }
        
        return $user?->can('inventory-movements.show') ?? false;
    }

    /**
     * Authorize the update controller action (PATCH /api/v1/inventory-movements/{id})
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot update inventory movements
        if ($user && $user->hasRole('customer')) {
            return false;
        }
        
        // Only allow updates to pending movements
        if ($model->status === 'completed') {
            return Response::deny('No se pueden modificar movimientos completados.');
        }
        
        return $user?->can('inventory-movements.update') ?? false;
    }

    /**
     * Authorize the destroy controller action (DELETE /api/v1/inventory-movements/{id})
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        
        // Customer users cannot delete inventory movements
        if ($user && $user->hasRole('customer')) {
            return false;
        }
        
        // Only allow deletion of pending movements
        if ($model->status === 'completed') {
            return Response::deny('No se pueden eliminar movimientos completados.');
        }
        
        return $user?->can('inventory-movements.destroy') ?? false;
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
<?php

namespace Modules\Billing\JsonApi\V1\CFDIItems;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CFDIItemAuthorizer implements Authorizer
{
    /**
     * Authorize index (list) requests.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        // Admin, Tech, and Customers can list items
        return $user->hasAnyPermission([
            'billing.cfdi-items.index',
        ]);
    }

    /**
     * Authorize store (create) requests.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        // Only Admin can create items
        return $user->hasPermissionTo('billing.cfdi-items.store');
    }

    /**
     * Authorize show (view) requests.
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        // Admin, Tech, and Customers can view items
        return $user->hasAnyPermission([
            'billing.cfdi-items.show',
        ]);
    }

    /**
     * Authorize update requests.
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        // Only Admin can update items
        return $user->hasPermissionTo('billing.cfdi-items.update');
    }

    /**
     * Authorize destroy (delete) requests.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        // Only Admin can delete items
        return $user->hasPermissionTo('billing.cfdi-items.destroy');
    }

    /**
     * Authorize showing related resources.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        return $user->hasAnyPermission([
            'billing.cfdi-items.show',
        ]);
    }

    /**
     * Authorize showing relationships.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        return $user->hasAnyPermission([
            'billing.cfdi-items.show',
        ]);
    }

    /**
     * Authorize updating relationships.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return Response::deny('Relationship updates not allowed', 403);
    }

    /**
     * Authorize attaching relationships.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return Response::deny('Relationship attachments not allowed', 403);
    }

    /**
     * Authorize detaching relationships.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return Response::deny('Relationship detachments not allowed', 403);
    }
}

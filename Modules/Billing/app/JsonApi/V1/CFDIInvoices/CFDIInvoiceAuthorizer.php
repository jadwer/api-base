<?php

namespace Modules\Billing\JsonApi\V1\CFDIInvoices;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CFDIInvoiceAuthorizer implements Authorizer
{
    /**
     * Authorize index (list) requests.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::deny('No autenticado.', 401);
        }

        // Admin, Tech, and Customers can list invoices
        return $user->hasAnyPermission([
            'billing.cfdi-invoices.index',
        ]);
    }

    /**
     * Authorize store (create) requests.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::deny('No autenticado.', 401);
        }

        // Only Admin can create invoices
        return $user->hasPermissionTo('billing.cfdi-invoices.store');
    }

    /**
     * Authorize show (view) requests.
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::deny('No autenticado.', 401);
        }

        // Admin, Tech, and Customers can view invoices
        // El cliente del portal solo ve SUS CFDI (misma regla que indexQuery);
        // sin esto, cambiar el id leia facturas ajenas (regla 8, 2026-11-09).
        return $user->hasAnyPermission(['billing.cfdi-invoices.show'])
            && \Modules\User\Support\UserAccess::seesDocument($user, 'billing.cfdi-invoices.index', $model->contact_id);
    }

    /**
     * Authorize update requests.
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::deny('No autenticado.', 401);
        }

        // Only Admin can update invoices
        return $user->hasPermissionTo('billing.cfdi-invoices.update');
    }

    /**
     * Authorize destroy (delete) requests.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::deny('No autenticado.', 401);
        }

        // Only Admin can delete invoices
        return $user->hasPermissionTo('billing.cfdi-invoices.destroy');
    }

    /**
     * Authorize showing related resources.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::deny('No autenticado.', 401);
        }

        // El cliente del portal solo ve SUS CFDI (misma regla que indexQuery);
        // sin esto, cambiar el id leia facturas ajenas (regla 8, 2026-11-09).
        return $user->hasAnyPermission(['billing.cfdi-invoices.show'])
            && \Modules\User\Support\UserAccess::seesDocument($user, 'billing.cfdi-invoices.index', $model->contact_id);
    }

    /**
     * Authorize showing relationships.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::deny('No autenticado.', 401);
        }

        // El cliente del portal solo ve SUS CFDI (misma regla que indexQuery);
        // sin esto, cambiar el id leia facturas ajenas (regla 8, 2026-11-09).
        return $user->hasAnyPermission(['billing.cfdi-invoices.show'])
            && \Modules\User\Support\UserAccess::seesDocument($user, 'billing.cfdi-invoices.index', $model->contact_id);
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

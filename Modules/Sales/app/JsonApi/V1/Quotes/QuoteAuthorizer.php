<?php

namespace Modules\Sales\JsonApi\V1\Quotes;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class QuoteAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('quotes.index') ?? false;
    }

    /**
     * Authorize the store controller action.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('quotes.store') ?? false;
    }

    /**
     * Authorize the show controller action.
     */
    public function show(Request $request, object $model): bool|Response
    {
        return $this->canSee($request, $model);
    }

    /**
     * Authorize the update controller action.
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('quotes.update') ?? false;
    }

    /**
     * Authorize the destroy controller action.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('quotes.destroy') ?? false;
    }

    /**
     * Authorize the showRelated controller action.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->canSee($request, $model);
    }

    /**
     * Authorize the showRelationship controller action.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->canSee($request, $model);
    }

    /**
     * Personal con el permiso ve cualquier cotizacion; el cliente del portal
     * (tiene quotes.show) solo las suyas. Antes el show no validaba dueno.
     */
    private function canSee(Request $request, object $model): bool
    {
        $user = $request->user();
        if (! $user || ! $user->can('quotes.show')) {
            return false;
        }

        return \Modules\User\Support\UserAccess::seesDocument($user, 'quotes.index', $model->contact_id);
    }

    /**
     * Authorize the updateRelationship controller action.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('quotes.update') ?? false;
    }

    /**
     * Authorize the attachRelationship controller action.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('quotes.update') ?? false;
    }

    /**
     * Authorize the detachRelationship controller action.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('quotes.update') ?? false;
    }
}

<?php

namespace Modules\Ecommerce\JsonApi\V1\Currencies;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CurrencyAuthorizer implements Authorizer
{
    /**
     * Authorize index (list all currencies)
     * Public endpoint - anyone can view currencies
     */
    public function index(Request $request): bool|Response
    {
        return true;
    }

    /**
     * Authorize store (create currency)
     * Only admins can create currencies
     */
    public function store(Request $request): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        return Response::deny('Only administrators can create currencies.');
    }

    /**
     * Authorize show (view currency)
     * Public endpoint - anyone can view a currency
     */
    public function show(Request $request, object $model): bool|Response
    {
        return true;
    }

    /**
     * Authorize update (edit currency)
     * Only admins can update currencies
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        return Response::deny('Only administrators can update currencies.');
    }

    /**
     * Authorize destroy (delete currency)
     * Only admins can delete currencies
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        return Response::deny('Only administrators can delete currencies.');
    }

    // =========================================================================
    // 5 RELATIONSHIP METHODS (Required by LaravelJsonApi\Contracts\Auth\Authorizer)
    // =========================================================================

    /**
     * Authorize showing related resources
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize showing a relationship
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize updating a relationship
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize attaching to a relationship
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize detaching from a relationship
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}

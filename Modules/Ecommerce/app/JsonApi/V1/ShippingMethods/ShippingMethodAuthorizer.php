<?php

namespace Modules\Ecommerce\JsonApi\V1\ShippingMethods;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ShippingMethodAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user() !== null;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user && $user->hasAnyRole(['god', 'admin']);
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user() !== null;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user && $user->hasAnyRole(['god', 'admin']);
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user && $user->hasAnyRole(['god', 'admin']);
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
        return false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }
}

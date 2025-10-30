<?php

namespace Modules\Ecommerce\JsonApi\V1\CheckoutSessions;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CheckoutSessionAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user && ($user->can('ecommerce.checkout-sessions.index')
            || $user->hasRole(['god', 'admin', 'tech']));
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user && ($user->can('ecommerce.checkout-sessions.store')
            || $user->hasAnyRole(['god', 'admin', 'tech', 'customer']));
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        if (isset($model->user_id) && $model->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only view your own checkout sessions.');
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if (isset($model->status) && in_array($model->status, ['completed', 'cancelled'])) {
            return Response::deny('Cannot modify completed or cancelled checkout sessions.');
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        if (isset($model->user_id) && $model->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only modify your own checkout sessions.');
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        if (isset($model->user_id) && isset($model->status)
            && $model->user_id === $user->id
            && !in_array($model->status, ['completed'])) {
            return true;
        }

        return Response::deny('You cannot delete this checkout session.');
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

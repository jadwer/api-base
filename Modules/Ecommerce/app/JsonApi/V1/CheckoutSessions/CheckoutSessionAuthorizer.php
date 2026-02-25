<?php

namespace Modules\Ecommerce\JsonApi\V1\CheckoutSessions;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CheckoutSessionAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.checkout-sessions.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.checkout-sessions.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.checkout-sessions.show')) {
            return true;
        }
        return isset($model->user_id) && $model->user_id === $user->id;
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
        if ($user->can('ecommerce.checkout-sessions.update')) {
            return true;
        }
        return isset($model->user_id) && $model->user_id === $user->id;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.checkout-sessions.destroy')) {
            return true;
        }
        // Owner can delete only if not completed
        if (isset($model->user_id) && isset($model->status)
            && $model->user_id === $user->id
            && !in_array($model->status, ['completed'])) {
            return true;
        }
        return false;
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

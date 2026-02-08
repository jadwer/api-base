<?php

namespace Modules\Ecommerce\JsonApi\V1\PaymentTransactions;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class PaymentTransactionAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user && $user->hasAnyRole(['god', 'admin', 'tech']);
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user && $user->hasAnyRole(['god', 'admin', 'tech']);
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

        $session = $model->checkoutSession ?? null;
        if ($session && isset($session->user_id) && $session->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only view your own payment transactions.');
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user && $user->hasAnyRole(['god', 'admin']);
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user && $user->hasRole('god');
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

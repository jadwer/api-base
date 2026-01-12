<?php

namespace Modules\Reports\JsonApi\V1\TrialBalances;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class TrialBalanceAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        return $user->hasAnyRole(['god', 'admin']) || $user->hasPermissionTo('reports.trial-balances.index');
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (!$user) {
            return Response::deny('Unauthenticated', 401);
        }

        return $user->hasAnyRole(['god', 'admin']) || $user->hasPermissionTo('reports.trial-balances.show');
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
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

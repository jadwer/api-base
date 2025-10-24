<?php

namespace Modules\Accounting\JsonApi\V1\AccountBalances;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class AccountBalanceAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        Log::info('AccountBalanceAuthorizer@index called', [
            'user_id' => $request->user()?->id,
            'model_class' => $modelClass,
        ]);
        
        $user = $request->user();
        return $user?->can('accountbalances.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        Log::info('AccountBalanceAuthorizer@store called', [
            'user_id' => $request->user()?->id,
            'model_class' => $modelClass,
        ]);
        
        $user = $request->user();
        return $user?->can('accountbalances.store') ?? false;
    }
    
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('accountbalances.show') ?? false;
    }
    
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('accountbalances.update') ?? false;
    }
    
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('accountbalances.destroy') ?? false;
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

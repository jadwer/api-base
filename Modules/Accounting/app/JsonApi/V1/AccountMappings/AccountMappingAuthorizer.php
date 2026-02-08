<?php

namespace Modules\Accounting\JsonApi\V1\AccountMappings;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class AccountMappingAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('account-mappings.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('account-mappings.store') ?? false;
    }
    
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('account-mappings.show') ?? false;
    }
    
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('account-mappings.update') ?? false;
    }
    
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('account-mappings.destroy') ?? false;
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

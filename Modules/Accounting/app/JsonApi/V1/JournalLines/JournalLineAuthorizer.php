<?php

namespace Modules\Accounting\JsonApi\V1\JournalLines;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class JournalLineAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        Log::info('JournalLineAuthorizer@index called', [
            'user_id' => $request->user()?->id,
            'model_class' => $modelClass,
        ]);
        
        $user = $request->user();
        return $user?->can('accounting.journal-lines.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        Log::info('JournalLineAuthorizer@store called', [
            'user_id' => $request->user()?->id,
            'model_class' => $modelClass,
        ]);
        
        $user = $request->user();
        return $user?->can('accounting.journal-lines.store') ?? false;
    }
    
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('accounting.journal-lines.show') ?? false;
    }
    
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('accounting.journal-lines.update') ?? false;
    }
    
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('accounting.journal-lines.destroy') ?? false;
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
